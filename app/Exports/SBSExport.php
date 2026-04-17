<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\Quota;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SBSExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected Collection $contracts;
    protected string $reportMonth;
    protected $refinancedContractsCache = null;
    protected $clientClassifications = null;

    public function __construct($contracts, string $reportMonth)
    {
        $this->contracts = $contracts instanceof Collection ? $contracts : collect($contracts);
        $this->reportMonth = $reportMonth; // "2025/09"
        $this->preloadRefinancedContracts();
    }

    private function preloadRefinancedContracts()
    {
        if ($this->contracts->isEmpty()) {
            $this->refinancedContractsCache = collect();
            return;
        }

        // Obtener todos los documentos y group_names únicos
        $documents = $this->contracts->pluck('document')->filter()->unique()->values();
        $groupNames = $this->contracts->pluck('group_name')->filter()->unique()->values();

        if ($documents->isEmpty() && $groupNames->isEmpty()) {
            $this->refinancedContractsCache = collect();
            return;
        }

        // Una sola consulta para obtener todos los contratos relacionados
        $relatedContracts = Contract::where(function ($query) use ($documents, $groupNames) {
            if ($documents->isNotEmpty()) {
                $query->whereIn('document', $documents);
            }
            if ($groupNames->isNotEmpty()) {
                if ($documents->isNotEmpty()) {
                    $query->orWhereIn('group_name', $groupNames);
                } else {
                    $query->whereIn('group_name', $groupNames);
                }
            }
        })->get();

        // Crear un cache agrupado por documento o group_name
        $this->refinancedContractsCache = $relatedContracts->groupBy(function ($contract) {
            return $contract->client_type === 'Personal'
                ? ($contract->document ?? '')
                : ($contract->group_name ?? '');
        })->map(function ($group) {
            return $group->pluck('id')->toArray();
        });
    }

    public function collection()
    {
        $rows = collect();

        // Calcular la fecha de corte una vez
        $reportDate = Carbon::createFromFormat('Y/m', $this->reportMonth)->endOfMonth();

        // Pre-cargar todas las cuotas de todos los contratos en una sola consulta
        $contractIds = $this->contracts->pluck('id')->toArray();
        $allQuotas = Quota::whereIn('contract_id', $contractIds)
            ->get()
            ->groupBy('contract_id');

        // Pre-cargar todos los pagos en una sola consulta (solo pagos no eliminados)
        $quotaIds = $allQuotas->flatten()->pluck('id')->toArray();
        $allPayments = collect();
        if (!empty($quotaIds)) {
            $allPayments = Payment::whereIn('quota_id', $quotaIds)
                ->where('deleted', 0)
                ->get()
                ->groupBy('quota_id');
        }

        // Agregar las cuotas precargadas a cada contrato y sus pagos
        foreach ($this->contracts as $contract) {
            $quotas = $allQuotas->get($contract->id, collect());
            $contract->setRelation('quotas', $quotas);

            // Agregar pagos a cada cuota
            $quotas->each(function ($quota) use ($allPayments) {
                $quota->setRelation('payments', $allPayments->get($quota->id, collect()));
            });
        }

        // Pre-calcular calificaciones SBS únicas por cliente
        $this->preCalculateClientClassifications($reportDate);

        foreach ($this->contracts as $contract) {

            // Datos comunes de ubicación desde el contrato
            $district   = optional($contract->district)->name ?? '';
            $province   = optional(optional($contract->district)->province)->name ?? '';
            $department = optional(optional(optional($contract->district)->province)->department)->name ?? '';
            $phone      = $contract->phone ?? '';

            // BASE del crédito
            $baseCredito = $contract->number_pagare ?? '';

            //Calculos de deuda
            $currentDirectDebt = $this->calculateDirectDebt($contract, $reportDate);
            $refinancedDebt = $this->calculateRefinancedDebt($contract, $reportDate);

            $overdueDirectDebtLessThan30 = $this->overdueDirectDebtLessThan30($contract, $reportDate);
            $overdueDirectDebtGreaterThan30 = $this->overdueDirectDebtGreaterThan30($contract, $reportDate);

            $guaranteedDebt = $this->calculateGuaranteedDebt($contract, $reportDate);
            $daysOverdue = $this->calculateDaysOverdue($contract, $reportDate);
            
            // Obtener calificación SBS única del cliente desde el cache
            $clientKey = $this->getClientKey($contract);
            $classificationSBS = $this->clientClassifications->get($clientKey, '0');
            
            // Estado: 1 si días vencidos > 180 y clasificación = 4 (Pérdida)
            $estado = ($daysOverdue > 180 && $classificationSBS == '4') ? '1' : '';
            
            // ✅ VALIDACIÓN TEMPRANA: Para grupos, verificar si todas las cuotas están pagadas
            // Esto evita procesar grupos donde todas las cuotas tienen paid=1 y debt=0
            if ($contract->client_type === 'Grupo' && $contract->relationLoaded('quotas')) {
                $allQuotasPaid = $contract->quotas->every(function ($quota) {
                    return $quota->paid == 1 && $quota->debt <= 0.01;
                });
                
                if ($allQuotasPaid && $contract->quotas->isNotEmpty()) {
                    continue; // Todas las cuotas están pagadas, saltar el contrato
                }
            }
            
            // ✅ VALIDACIÓN: Verificar si hay deuda real después de calcular
            // Esta es la validación más confiable porque usa los cálculos reales de deuda
            $totalDebt = $currentDirectDebt + $refinancedDebt + $overdueDirectDebtLessThan30 + $overdueDirectDebtGreaterThan30;
            
            // Para grupos, verificar también deuda avalada
            if ($contract->client_type === 'Grupo') {
                // Si no hay deuda directa ni avalada, saltar el contrato completo
                if ($totalDebt <= 0 && $guaranteedDebt <= 0) {
                    continue; // No generar fila si no hay deuda
                }
            } else {
                // Para contratos personales, solo verificar deuda directa
                if ($totalDebt <= 0) {
                    continue; // No generar fila si no hay deuda
                }
            }
            
            // -------------------------
            // CASO PERSONAL: 1 fila
            // -------------------------
            if ($contract->client_type !== 'Grupo') {

                $docNumber = $contract->document ?? '';
                [$apePat, $apeMat, $nombres] = $this->splitFullName($contract->name ?? '');
                $address = $contract->address ?? '';

                $rows->push($this->buildRow(
                    $baseCredito,
                    1, // normalmente personal = 1 (Persona Natural)
                    $docNumber,
                    $apePat,
                    $apeMat,
                    $nombres,
                    $address,
                    $district,
                    $province,
                    $department,
                    $phone,
                    $estado,
                    $currentDirectDebt,
                    $refinancedDebt,
                    $overdueDirectDebtLessThan30,
                    $overdueDirectDebtGreaterThan30,
                    $guaranteedDebt,
                    $classificationSBS,
                    $daysOverdue,
                    $contract
                ));

                continue;
            }

            // -------------------------
            // CASO GRUPO: Cada persona es titular con sus avales
            // -------------------------
            $people = $this->decodePeople($contract->people);

            $n = count($people);
            if ($n === 0) {
                continue;
            }

            // Para cada persona del grupo:
            // 1. Crear una fila como TITULAR con número base-X
            // 2. Crear filas para cada una de las demás personas como AVALES con número base-X-Y
            for ($i = 0; $i < $n; $i++) {
                $titular = $people[$i];
                $docTitular = $titular['document'] ?? '';
                $fullNameTitular = $titular['name'] ?? '';
                [$apePat, $apeMat, $nombres] = $this->splitFullName($fullNameTitular);
                $address = $titular['address'] ?? ($contract->address ?? '');

                // Número de crédito del titular: base-X (ej: 15047-1, 15047-2)
                $numeroTitular = $baseCredito . '-' . ($i + 1);

                // Agregar fila del TITULAR
                $rows->push($this->buildRow(
                    $numeroTitular,
                    1,  // Tipo persona: 1 = Titular
                    $docTitular,
                    $apePat,
                    $apeMat,
                    $nombres,
                    $address,
                    $district,
                    $province,
                    $department,
                    $phone,
                    $estado,
                    $currentDirectDebt,
                    $refinancedDebt,
                    $overdueDirectDebtLessThan30,
                    $overdueDirectDebtGreaterThan30,
                    $guaranteedDebt,
                    $classificationSBS,
                    $daysOverdue,
                    $contract
                ));

                // AVALES: Crear filas para todas las demás personas como avales del titular actual
                $avalIndex = 1; // Contador para el número de aval (1, 2, 3, ...)
                for ($j = 0; $j < $n; $j++) {
                    // Saltar si es la misma persona (el titular no puede ser su propio aval)
                    if ($i === $j) {
                        continue;
                    }

                    $aval = $people[$j];
                    $docAval = $aval['document'] ?? '';
                    $fullNameAval = $aval['name'] ?? '';
                    
                    [$apePat, $apeMat, $nombres] = $this->splitFullName($fullNameAval);
                    $address = $aval['address'] ?? ($contract->address ?? '');
                    
                    // Número de crédito del aval: base-X-Y (ej: 15047-1-1, 15047-1-2)
                    $numeroAval = $numeroTitular . '-' . $avalIndex;
                    
                    $rows->push($this->buildRow(
                        $numeroAval,
                        3,  // Tipo persona: 3 = Aval
                        $docAval,
                        $apePat,
                        $apeMat,
                        $nombres,
                        $address,
                        $district,
                        $province,
                        $department,
                        $phone,
                        $estado,
                        $currentDirectDebt,
                        $refinancedDebt,
                        $overdueDirectDebtLessThan30,
                        $overdueDirectDebtGreaterThan30,
                        $guaranteedDebt,
                        $classificationSBS,
                        $daysOverdue,
                        $contract
                    ));
                    
                    $avalIndex++; // Incrementar el contador de avales
                }
            }
        }

        return $rows;
    }

    private function buildRow(
        string $numeroCredito,
        int $tipoPersona,
        string $docNumber,
        string $apePat,
        string $apeMat,
        string $nombres,
        string $address,
        string $district,
        string $province,
        string $department,
        string $phone,
        string $estado,
        float $currentDirectDebt,
        float $refinancedDebt,
        float $overdueDirectDebtLessThan30,
        float $overdueDirectDebtGreaterThan30,
        float $guaranteedDebt,
        string $classificationSBS,
        int $daysOverdue,
        $contract = null
    ): array {
        $codigoEntidad = '';    // Dejar vacío según especificación
        $tipoCredito = 5;       // 5 = Créditos Microempresas (ajustar según negocio)

        // Detectar tipo de documento según longitud
        $docType = $this->detectDocumentType($docNumber);
        
        // Formatear número de documento según tipo
        $docNumber = $this->formatDocumentNumber($docNumber, $docType);
        
        // Normalizar texto (MAYÚSCULAS sin acentos ni caracteres especiales)
        $apePat = $this->normalizeText($apePat);
        $apeMat = $this->normalizeText($apeMat);
        $nombres = $this->normalizeText($nombres);
        $address = $this->normalizeText($address);
        $district = $this->normalizeText($district);
        $province = $this->normalizeText($province);
        $department = $this->normalizeText($department);
        
        // Razón Social (solo si es persona jurídica - RUC)
        $razonSocial = ($docType == 6) ? $this->normalizeText($nombres) : '';
        
        // Calcular el monto TOTAL del contrato (toda la deuda pendiente)
        $montoTotal = $currentDirectDebt + $refinancedDebt + $overdueDirectDebtLessThan30 + $overdueDirectDebtGreaterThan30;
        
        // Inicializar todos los montos en 0
        $mnDeudaDirectaVigente = 0;
        $mnDeudaDirectaRefinanciada = 0;
        $mnDeudaDirectaVencida30 = 0;
        $mnDeudaDirectaVencidaMayor30 = 0;
        $mnDeudaAvalada = 0;
        
        // Si es Avalista (tipo persona = 3): todo va en deuda avalada
        if ($tipoPersona == 3) {
            $mnDeudaAvalada = $guaranteedDebt;
        } else {
            // Para titulares: distribuir según calificación SBS
            // Calificación 0 (Normal, 0-8 días): todo en Vigente
            if ($classificationSBS == '0') {
                $mnDeudaDirectaVigente = $montoTotal;
            }
            // Calificación 1 (CPP, 9-30 días): todo en Vencida <= 30
            elseif ($classificationSBS == '1') {
                $mnDeudaDirectaVencida30 = $montoTotal;
            }
            // Calificación 2, 3, 4 (Deficiente, Dudoso, Pérdida, > 30 días): todo en Vencida > 30
            else {
                $mnDeudaDirectaVencidaMayor30 = $montoTotal;
            }
        }
        
        // Formatear montos como texto con punto decimal
        $mnDeudaDirectaVigente = $this->formatAmount($mnDeudaDirectaVigente);
        $mnDeudaDirectaRefinanciada = $this->formatAmount($mnDeudaDirectaRefinanciada);
        $mnDeudaDirectaVencida30 = $this->formatAmount($mnDeudaDirectaVencida30);
        $mnDeudaDirectaVencidaMayor30 = $this->formatAmount($mnDeudaDirectaVencidaMayor30);
        $mnDeudaAvalada = $this->formatAmount($mnDeudaAvalada);
        
        // Días vencidos: SIEMPRE mostrar el valor, incluso si es 0
        $daysOverdueFormatted = $daysOverdue;

        return [
            (string) $this->reportMonth,              // 1 Mes de Reporte (AAAA/MM)
            '',                                       // 2 Código Entidad (vacío)
            (string) $numeroCredito,                  // 3 Número del Crédito
            (string) $docType,                        // 4 Tipo Documento Identidad
            (string) $docNumber,                      // 5 N° Documento Identidad
            (string) $razonSocial,                    // 6 Razon Social
            (string) $apePat,                         // 7 Apellido Paterno
            (string) $apeMat,                         // 8 Apellido Materno
            (string) $nombres,                        // 9 Nombres
            (string) $tipoPersona,                    // 10 Tipo Persona (1,2,3,4,5)
            (string) $tipoCredito,                    // 11 Tipo de Crédito
            (string) $mnDeudaDirectaVigente,          // 12 MN Deuda Directa Vigente
            (string) $mnDeudaDirectaRefinanciada,     // 13 MN Deuda Directa Refinanciada
            (string) $mnDeudaDirectaVencida30,        // 14 MN Deuda Directa Vencida <= 30
            (string) $mnDeudaDirectaVencidaMayor30,   // 15 MN Deuda Directa Vencida > 30
            '',                                       // 16 MN Deuda Directa Cobranza Judicial
            '',                                       // 17 MN Deuda Indirecta
            (string) $mnDeudaAvalada,                 // 18 MN Deuda Avalada
            '',                                       // 19 MN Linea de Crédito
            '',                                       // 20 MN Creditos Castigados
            '',                                       // 21 ME Deuda Directa Vigente
            '',                                       // 22 ME Deuda Directa Refinanciada
            '',                                       // 23 ME Deuda Directa Vencida <= 30
            '',                                       // 24 ME Deuda Directa Vencida > 30
            '',                                       // 25 ME Deuda Directa Cobranza Judicial
            '',                                       // 26 ME Deuda Indirecta
            '',                                       // 27 ME Deuda Avalada
            '',                                       // 28 ME Linea de Crédito
            '',                                       // 29 ME Creditos Castigados
            (string) $classificationSBS,              // 30 Calificación SBS
            (string) $daysOverdueFormatted,           // 31 Número días vencidos o morosos
            '',                                       // 32 Dirección
            '',                                       // 33 Distrito
            '',                                       // 34 Provincia
            '',                                       // 35 Departamento
            '',                                     // 36 Teléfono
            (string) $estado,                         // 37 Estado
        ];
    }

    private function decodePeople($peopleRaw): array
    {
        if (!$peopleRaw) return [];
        $decoded = json_decode($peopleRaw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function headings(): array
    {
        return [
            "Mes de Reporte",
            "Código Entidad",
            "Número del Crédito",
            "Tipo Documento Identidad",
            "N° Documento Identidad",
            "Razon Social",
            "Apellido Paterno",
            "Apellido Materno",
            "Nombres",
            "Tipo Persona",
            "Tipo de Crédito",
            "MN Deuda Directa Vigente",
            "MN Deuda Directa Refinanciada",
            "MN Deuda Directa Vencida < = 30",
            "MN Deuda Directa Vencida > 30",
            "MN Deuda Directa Cobranza Judicial",
            "MN Deuda Indirecta (avales,cartas fianza,credito)",
            "MN Deuda Avalada",
            "MN Linea de Crédito",
            "MN Creditos Castigados",
            "ME Deuda Directa Vigente",
            "ME Deuda Directa Refinanciada",
            "ME Deuda Directa Vencida < = 30",
            "ME Deuda Directa Vencida > 30",
            "ME Deuda Directa Cobranza Judicial",
            "ME Deuda Indirecta (avales,cartas fianza,credito)",
            "ME Deuda Avalada",
            "ME Linea de Crédito",
            "ME Creditos Castigados",
            "Calificación SBS",
            "Número días vencidos o morosos",
            "Dirección",
            "Distrito",
            "Provincia",
            "Departamento",
            "Teléfono",
            "Estado",
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Aplicar rosa/magenta a toda la fila de cabecera
        $sheet->getStyle('A1:AK1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C2358C'] // Rosa/Magenta para todos
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'], // Texto blanco
                'size' => 8,
                'name' => 'Trebuchet MS'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Aplicar azul solo a la columna B
        $sheet->getStyle('B1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F3A93'] // Azul oscuro
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 8,
                'name' => 'Trebuchet MS'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Aplicar rosa claro a las columnas ME (U1 a AC1)
        $sheet->getStyle('U1:AC1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E89DC3'] // Rosa claro para columnas ME
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 8,
                'name' => 'Trebuchet MS'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Aplicar azul oscuro a las columnas AF, AG, AH, AI, AJ (Dirección, Distrito, Provincia, Departamento, Teléfono)
        $sheet->getStyle('AF1:AJ1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F3A93'] // Azul oscuro
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 8,
                'name' => 'Trebuchet MS'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ]);

        // Ajustar altura de la fila de cabecera
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Aplicar estilos a los datos (desde la fila 2 hasta el final)
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:AK' . $lastRow)->applyFromArray([
            'font' => [
                'name' => 'Calibri Light',
                'size' => 12
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        return [];
    }

    /**
     * Formatea todas las columnas como TEXTO para evitar que Excel modifique los datos
     * Esto previene que Excel elimine ceros a la izquierda, cambie fechas, etc.
     */
    public function columnFormats(): array
    {
        // Formato de texto (@) para todas las columnas de la A a la AK (37 columnas)
        return [
            'A' => NumberFormat::FORMAT_TEXT,  // Mes de Reporte
            'B' => NumberFormat::FORMAT_TEXT,  // Código Entidad
            'C' => NumberFormat::FORMAT_TEXT,  // Número del Crédito
            'D' => NumberFormat::FORMAT_TEXT,  // Tipo Documento Identidad
            'E' => NumberFormat::FORMAT_TEXT,  // N° Documento Identidad
            'F' => NumberFormat::FORMAT_TEXT,  // Razon Social
            'G' => NumberFormat::FORMAT_TEXT,  // Apellido Paterno
            'H' => NumberFormat::FORMAT_TEXT,  // Apellido Materno
            'I' => NumberFormat::FORMAT_TEXT,  // Nombres
            'J' => NumberFormat::FORMAT_TEXT,  // Tipo Persona
            'K' => NumberFormat::FORMAT_TEXT,  // Tipo de Crédito
            'L' => NumberFormat::FORMAT_TEXT,  // MN Deuda Directa Vigente
            'M' => NumberFormat::FORMAT_TEXT,  // MN Deuda Directa Refinanciada
            'N' => NumberFormat::FORMAT_TEXT,  // MN Deuda Directa Vencida <= 30
            'O' => NumberFormat::FORMAT_TEXT,  // MN Deuda Directa Vencida > 30
            'P' => NumberFormat::FORMAT_TEXT,  // MN Deuda Directa Cobranza Judicial
            'Q' => NumberFormat::FORMAT_TEXT,  // MN Deuda Indirecta
            'R' => NumberFormat::FORMAT_TEXT,  // MN Deuda Avalada
            'S' => NumberFormat::FORMAT_TEXT,  // MN Linea de Crédito
            'T' => NumberFormat::FORMAT_TEXT,  // MN Creditos Castigados
            'U' => NumberFormat::FORMAT_TEXT,  // ME Deuda Directa Vigente
            'V' => NumberFormat::FORMAT_TEXT,  // ME Deuda Directa Refinanciada
            'W' => NumberFormat::FORMAT_TEXT,  // ME Deuda Directa Vencida <= 30
            'X' => NumberFormat::FORMAT_TEXT,  // ME Deuda Directa Vencida > 30
            'Y' => NumberFormat::FORMAT_TEXT,  // ME Deuda Directa Cobranza Judicial
            'Z' => NumberFormat::FORMAT_TEXT,  // ME Deuda Indirecta
            'AA' => NumberFormat::FORMAT_TEXT, // ME Deuda Avalada
            'AB' => NumberFormat::FORMAT_TEXT, // ME Linea de Crédito
            'AC' => NumberFormat::FORMAT_TEXT, // ME Creditos Castigados
            'AD' => NumberFormat::FORMAT_TEXT, // Calificación SBS
            'AE' => NumberFormat::FORMAT_TEXT, // Número días vencidos o morosos
            'AF' => NumberFormat::FORMAT_TEXT, // Dirección
            'AG' => NumberFormat::FORMAT_TEXT, // Distrito
            'AH' => NumberFormat::FORMAT_TEXT, // Provincia
            'AI' => NumberFormat::FORMAT_TEXT, // Departamento
            'AJ' => NumberFormat::FORMAT_TEXT, // Teléfono
            'AK' => NumberFormat::FORMAT_TEXT, // Estado
        ];
    }

    private function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') return ['', '', ''];

        $parts = explode(' ', $fullName);

        if (count($parts) >= 3) {
            $apeMat = array_pop($parts);
            $apePat = array_pop($parts);
            $nombres = implode(' ', $parts);
            return [$apePat, $apeMat, $nombres];
        }

        if (count($parts) === 2) {
            // Ajusta si tu formato es distinto
            return [$parts[1], '', $parts[0]];
        }

        return ['', '', $parts[0]];
    }

    /**
     * Detecta el tipo de documento según su longitud
     * 1 = DNI (8 dígitos)
     * 3 = Carné de extranjería (9 dígitos)
     * 4 = Pasaporte (otros)
     * 6 = RUC (11 dígitos)
     */
    private function detectDocumentType(string $docNumber): int
    {
        $length = strlen(trim($docNumber));
        
        if ($length == 8) {
            return 1; // DNI
        } elseif ($length == 11) {
            return 6; // RUC
        } elseif ($length == 9) {
            return 3; // Carné de extranjería
        } else {
            return 4; // Pasaporte
        }
    }

    /**
     * Formatea el número de documento según su tipo
     * DNI: 8 dígitos con ceros a la izquierda
     * RUC: 11 dígitos
     */
    private function formatDocumentNumber(string $docNumber, int $docType): string
    {
        $docNumber = trim($docNumber);
        
        if ($docType == 1) {
            // DNI: 8 dígitos con ceros a la izquierda
            return str_pad($docNumber, 8, '0', STR_PAD_LEFT);
        } elseif ($docType == 6) {
            // RUC: 11 dígitos
            return str_pad($docNumber, 11, '0', STR_PAD_LEFT);
        }
        
        return $docNumber;
    }

    /**
     * Normaliza texto según especificaciones SBS:
     * - MAYÚSCULAS
     * - Sin acentos
     * - Sin caracteres especiales (*, ., -, +, #, $, etc.)
     */
    private function normalizeText(string $text): string
    {
        if (empty($text)) return '';
        
        // Convertir a mayúsculas
        $text = mb_strtoupper($text, 'UTF-8');
        
        // Eliminar acentos
        $text = $this->removeAccents($text);
        
        // Eliminar caracteres especiales según especificación
        // Permitir solo letras, números y espacios
        $text = preg_replace('/[^A-Z0-9\s]/', '', $text);
        
        // Eliminar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Elimina acentos de un texto
     */
    private function removeAccents(string $text): string
    {
        $unwanted_array = [
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U', 'Ñ'=>'N',
            'á'=>'A', 'é'=>'E', 'í'=>'I', 'ó'=>'O', 'ú'=>'U', 'ñ'=>'N'
        ];
        
        return strtr($text, $unwanted_array);
    }

    /**
     * Formatea monto como texto con punto decimal
     * Retorna cadena vacía si el monto es 0
     */
    private function formatAmount($amount): string
    {
        $amount = (float)$amount;
        
        if ($amount == 0) {
            return '';
        }
        
        // Formatear con 2 decimales y punto como separador decimal
        return number_format($amount, 2, '.', '');
    }

    /**
     * Pre-calcula las calificaciones SBS únicas por cliente
     * Según SBS: cada cliente debe tener UNA SOLA calificación
     * Se toma la peor calificación entre todos sus contratos
     */
    private function preCalculateClientClassifications($reportDate)
    {
        $this->clientClassifications = collect();
        
        foreach ($this->contracts as $contract) {
            $clientKey = $this->getClientKey($contract);
            $classification = $this->calculateSBSClassification($contract, $reportDate);
            
            // Si ya existe una clasificación para este cliente, quedarse con la peor
            $existingClassification = $this->clientClassifications->get($clientKey, '0');
            
            if ($classification > $existingClassification) {
                $this->clientClassifications->put($clientKey, $classification);
            }
        }
    }

    /**
     * Obtiene la clave única del cliente para agrupación
     */
    private function getClientKey($contract): string
    {
        if ($contract->client_type === 'Personal') {
            return 'P_' . ($contract->document ?? '');
        } else {
            return 'G_' . ($contract->group_name ?? '');
        }
    }

    /**Calculos */

    /**Ver si es refinanciado - usa cache para evitar consultas N+1 */
    private function isContractRefinanced($contract): bool
    {
        if (!$this->refinancedContractsCache) {
            return false;
        }

        $key = $contract->client_type === 'Personal'
            ? ($contract->document ?? '')
            : ($contract->group_name ?? '');

        if (empty($key)) {
            return false;
        }

        $refinancedIds = $this->refinancedContractsCache->get($key, []);
        // Es refinanciado si hay más de un contrato con el mismo documento/group_name
        // (incluyendo el actual, así que si count > 1 significa que hay otros contratos)
        return count($refinancedIds) > 1;
    }

    /**Deuda directa vigente - optimizado para usar relaciones precargadas */
    private function calculateDirectDebt($contract, $reportDate): float
    {
        // Usar las cuotas ya cargadas con with('quotas') en lugar de hacer nueva consulta
        //Cuota directa vigente sl ultimo dia del mes de reporte
        if ($contract->relationLoaded('quotas')) {
            return (float) $contract->quotas
                ->where('paid', 0)
                ->filter(function ($quota) use ($reportDate) {
                    return Carbon::parse($quota->date)->gt($reportDate);
                })
                ->filter(function ($quota) {
                    return $quota->debt > 0.01; // Solo cuotas con deuda real
                })
                ->sum('debt');
        }

        return 0.0; // Si no están cargadas, retornar 0 (no debería pasar con la optimización)
    }
    /**Deuda directa refinanciada - optimizado para usar relaciones precargadas 
     */
    private function calculateRefinancedDebt($contract, $reportDate): float
    {
        // Usar el método que verifica si es refinanciado basado en contratos anteriores
        $isRefinanced = $this->isContractRefinanced($contract);

        if (!$isRefinanced) {
            return 0;
        }

        // Si es refinanciado, calcular la deuda refinanciada sl ultimo dia del mes de reporte
        if ($contract->relationLoaded('quotas')) {
            return (float) $contract->quotas
                ->where('paid', 0)
                ->filter(function ($quota) use ($reportDate) {
                    return Carbon::parse($quota->date)->lte($reportDate);
                })
                ->filter(function ($quota) {
                    return $quota->debt > 0.01; // Solo cuotas con deuda real
                })
                ->sum('debt');
        }

        return 0.0; // Si no están cargadas, retornar 0 (no debería pasar con la optimización)
    }
    /**Deuda directa vencida <= 30 días
     *  cuota(s) no pagada(s)
     *  - al ultimo dia del mes de reporte
     *  - tiene menos de 30 dias de mora
     *  - CORREGIDO: usa relaciones precargadas y lógica correcta */
    private function overdueDirectDebtLessThan30($contract, $reportDate): float
    {
        if (!$contract->relationLoaded('quotas')) {
            return 0.0;
        }

        // Filtrar cuotas no pagadas con fecha <= reportDate (vencidas) y más de 30 días de mora
        return (float) $contract->quotas
            ->where('paid', 0)
            ->filter(function ($quota) use ($reportDate) {
                $quotaDate = Carbon::parse($quota->date);
                // Debe estar vencida (fecha <= reportDate) y tener menos de 30 días de mora (calculado hasta último día del mes)
                if ($quotaDate->lte($reportDate)) {
                    $daysOverdue = $reportDate->diffInDays($quotaDate);
                    return $daysOverdue <= 30;
                }
                return false;
            })
            ->filter(function ($quota) {
                return $quota->debt > 0.01; // Solo cuotas con deuda real
            })
            ->sum('debt');
    }

    /**Deuda directa vencida > 30 días
     *  cuota(s) no pagada(s)
     *  - al ultimo dia del mes de reporte
     *  - CORREGIDO: usa relaciones precargadas y lógica correcta */
    private function overdueDirectDebtGreaterThan30($contract, $reportDate): float
    {
        if (!$contract->relationLoaded('quotas')) {
            return 0.0;
        }

        // Filtrar cuotas no pagadas con fecha <= reportDate (vencidas) y más de 30 días de mora
        return (float) $contract->quotas
            ->where('paid', 0)
            ->filter(function ($quota) use ($reportDate) {
                $quotaDate = Carbon::parse($quota->date);
                // Debe estar vencida (fecha <= reportDate) y tener mas de 30 días de mora (calculado hasta último día del mes)
                if ($quotaDate->lte($reportDate)) {
                    $daysOverdue = $reportDate->diffInDays($quotaDate);
                    return $daysOverdue > 30;
                }
                return false;
            })
            ->filter(function ($quota) {
                return $quota->debt > 0.01; // Solo cuotas con deuda real
            })
            ->sum('debt');
    }

    /**Deuda directa cobranza judicial (??) */

    /**Deuda indirecta (avales,cartas fianza,credito) en soles*/
    private function indirectDebt(): float
    {

        return 0.0;
    }
    /**Deuda avalada en soles*/

    /**
     * Calcula la deuda avalada para un contrato
     * La deuda avalada es la deuda total (vigente + vencida) que está siendo avalada
     * Solo aplica para contratos de tipo Grupo
     * 
     * Según especificaciones SBS:
     * "Saldo de deuda directa y castigada del titular que se encuentra avalada 
     * por una tercera persona al último día del mes de reporte"
     */
    private function calculateGuaranteedDebt($contract, $reportDate): float
    {
        // Solo para grupos (donde hay avalistas)
        if ($contract->client_type !== 'Grupo') {
            return 0.0;
        }

        if (!$contract->relationLoaded('quotas')) {
            return 0.0;
        }

        // Calcular TODA la deuda del contrato (vigente + vencida + refinanciada)
        // al último día del mes de reporte
        // Solo cuotas con paid = 0 Y debt > 0.01 (tolerancia para redondeos)
        $totalDebt = (float) $contract->quotas
            ->where('paid', 0)
            ->filter(function ($quota) {
                return $quota->debt > 0.01; // Solo cuotas con deuda real
            })
            ->sum('debt');

        return $totalDebt;
    }
    /**Calificación SBS según días de mora
     * 0: Normal (sin mora o <= 8 días)
     * 1: CPP - Con Problemas Potenciales (> 8 y <= 30 días)
     * 2: Deficiente (> 30 y <= 60 días)
     * 3: Dudoso (> 60 y <= 120 días)
     * 4: Pérdida (> 120 días)
     */
    private function calculateSBSClassification($contract, $reportDate): string
    {
        $maxDaysOverdue = $this->calculateMaxDaysOverdue($contract, $reportDate);

        if ($maxDaysOverdue <= 8) {
            return '0'; // Normal
        } elseif ($maxDaysOverdue <= 30) {
            return '1'; // CPP
        } elseif ($maxDaysOverdue <= 60) {
            return '2'; // Deficiente
        } elseif ($maxDaysOverdue <= 120) {
            return '3'; // Dudoso
        } else {
            return '4'; // Pérdida
        }
    }

    /**Calcula el número máximo de días vencidos entre todas las cuotas vencidas
     * Los días de mora se calculan hasta el último día del mes de reporte
     */
    private function calculateMaxDaysOverdue($contract, $reportDate): int
    {
        if (!$contract->relationLoaded('quotas')) {
            return 0;
        }

        $maxDays = 0;

        $contract->quotas
            ->where('paid', 0)
            ->filter(function ($quota) use ($reportDate) {
                // Solo considerar cuotas vencidas hasta el último día del mes de reporte
                return Carbon::parse($quota->date)->lte($reportDate);
            })
            ->each(function ($quota) use ($reportDate, &$maxDays) {
                $quotaDate = Carbon::parse($quota->date);
                // Calcular días de mora desde la fecha de la cuota hasta el último día del mes de reporte
                $daysOverdue = $reportDate->diffInDays($quotaDate);
                if ($daysOverdue > $maxDays) {
                    $maxDays = $daysOverdue;
                }
            });

        return $maxDays;
    }

    /**Número de días vencidos o morosos calculados hasta el último día del mes de reporte */
    private function calculateDaysOverdue($contract, $reportDate): int
    {
        return $this->calculateMaxDaysOverdue($contract, $reportDate);
    }
}