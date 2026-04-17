<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\EndingContractsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;
use App\Models\Pdf as PdfModel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $contracts = Contract::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('seller', function ($q) use ($user) {
                    $q->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%')
                        ->orWhere('document', 'like', '%' . $name . '%')
                        ->orWhere('people', 'like', '%' . $name . '%');
                });
            })->when($request->seller_id, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            })->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })->latest('date')->latest('id')->paginate(20);

        // Filtrar sellers según el rol
        $sellers = User::seller()->where('state', 0)->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->orderBy('name', 'asc')->get();

        return view('contracts.index', compact('contracts', 'sellers'));
    }

    public function ending(Request $request)
    {
        $user = auth()->user();

        $start_date = $request->start_date ? $request->start_date : now();
        $end_date = $request->end_date ? $request->end_date : now();

        $contracts = Contract::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('seller', function ($q) use ($user) {
                    $q->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%')
                        ->orWhere('document', 'like', '%' . $name . '%')
                        ->orWhere('people', 'like', '%' . $name . '%');
                });
            })->when($request->seller_id, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            })->whereDate('last_payment_date', '>=', $start_date)->whereDate('last_payment_date', '<=', $end_date)
            ->oldest('last_payment_date');

        $requested_amount = $contracts->sum('requested_amount');

        $contracts = $contracts->paginate(20);

        $sellers = User::seller()->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->orderBy('name', 'asc')->get();

        return view('contracts.ending', compact('contracts', 'sellers', 'requested_amount'));
    }

    public function endingExcel(Request $request)
    {
        $name = "ContratosPorFinalizar_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new EndingContractsExport, $name);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'client_type' => 'required',
            'district_id' => 'required',
            'documents.*' => 'nullable|size:8|distinct',
            'names.*' => 'nullable|distinct',
            'addresses.*' => 'nullable',
            'address.*' => 'required',
            'document' => 'nullable|size:8',
            'name' => 'nullable',
            'group_name' => 'nullable',
            'phone' => 'nullable',
            'address' => 'nullable',
            'home_type' => 'nullable',
            'business_start_date' => 'nullable|date',
            'civil_status' => 'nullable',
            'husband_name' => 'nullable',
            'husband_document' => 'nullable|size:8',
            'seller_id' => 'required',
            'requested_amount' => 'required|numeric',
            'months_number' => 'required|numeric|min:1',
            'date' => 'required|date',
            'interest' => 'nullable|numeric',
            'quotas.*' => 'nullable|numeric|min:1',
        ]);

        $validator->sometimes(['document', 'name', 'phone', 'address', 'home_type', 'civil_status'], 'required', function ($request) {
            return $request->client_type == 'Personal';
        });

        $validator->sometimes(['group_name', 'documents.*', 'names.*', 'addresses.*', 'quotas.*'], 'required', function ($request) {
            return $request->client_type == 'Grupo';
        });

        $validator->sometimes(['husband_name', 'husband_document'], 'required', function ($request) {
            return $request->civil_status == 'Casado';
        });

        $validator->sometimes('interest', 'required', function ($request) {
            return $request->edit_interest == 1;
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        // Validación adicional: solo para clientes tipo 'Grupo' si se envían montos de cuotas,
        // su suma debe ser igual al monto solicitado
        if ($request->client_type == 'Grupo') {
            $quotasInput = $request->input('quotas');
            if (is_array($quotasInput) && count($quotasInput) > 0) {
                $sumQuotas = 0;
                foreach ($quotasInput as $q) {
                    $qNormalized = is_null($q) ? 0 : str_replace(',', '.', $q);
                    $sumQuotas += floatval($qNormalized);
                }

                $requestedAmount = is_null($request->requested_amount) ? 0 : str_replace(',', '.', $request->requested_amount);
                $requestedAmountFloat = floatval($requestedAmount);

                // Comparación con tolerancia para evitar problemas de redondeo
                if (abs($sumQuotas - $requestedAmountFloat) > 0.01) {
                    return response()->json([
                        'status' => false,
                        'error' => 'La suma de los montos de cuotas debe ser igual al monto total del contrato.'
                    ]);
                }
            }
        }

        if ($request->edit_interest == 1) {
            $interest_percentage = floatval($request->interest);
        } else {
            $interest_percentage = 3.75;
        }

        $quotas = $request->months_number * 4;
        $percentage = $quotas * $interest_percentage;
        $interest = $request->requested_amount * ($percentage / 100);
        $payable_amount = $request->requested_amount + $interest;
        $quota = $payable_amount / $quotas;
        $quota = ceil($quota * 10) / 10;

        $count = DB::table('config')->pluck('number_pagare')->first();

        $number_pagare = $count + 1;

        $date = Carbon::parse($request->date);

        $quota_dates = [];

        for ($i = 1; $i <= $quotas; $i++) {
            $quota_date = $date->copy()->addWeeks($i);

            $quota_dates[] = [
                'number' => $i,
                'date' => $quota_date->format('Y-m-d')
            ];
        }

        DB::beginTransaction();

        try {

            $contract = new Contract;
            $contract->client_type = $request->client_type;

            if ($request->client_type == 'Personal') {
                $contract->document = $request->document;
                $contract->name = $request->name;
                $contract->phone = $request->phone;
                $contract->address = $request->address;
                $contract->reference = $request->reference;
                $contract->home_type = $request->home_type;
                $contract->business_line = $request->business_line;
                $contract->business_address = $request->business_address;
                $contract->business_start_date = $request->business_start_date;
                $contract->civil_status = $request->civil_status;
                $contract->husband_name = $request->husband_name;
                $contract->husband_document = $request->husband_document;
            } elseif ($request->client_type == 'Grupo') {

                $people = [];

                for ($i = 0; $i < count($request->documents); $i++) {
                    $people[] = [
                        'document' => $request->documents[$i],
                        'name' => $request->names[$i],
                        'address' => $request->addresses[$i],
                        'quotes' => $request->quotas[$i],
                        'phone' => $request->phones[$i],
                    ];
                }


                $group_number = DB::table('settings')->selectRaw('group_number + 1 AS number')->pluck('number')->first();

                $contract->group_name = "Grupo {$group_number} - " . $request->group_name;
                $contract->people = json_encode($people);

                DB::table('settings')->update(['group_number' => $group_number]);
            }

            $contract->number_pagare = $number_pagare;

            $contract->seller_id = $request->seller_id;
            $contract->district_id = $request->district_id;
            $contract->requested_amount = $request->requested_amount;
            $contract->months_number = $request->months_number;
            $contract->quotas_number = $quotas;
            $contract->percentage = $percentage;
            $contract->interest = $interest;
            $contract->payable_amount = $payable_amount;
            $contract->quota_amount = $quota;
            $contract->date = $request->date;
            $contract->first_payment_date = reset($quota_dates)['date'];
            $contract->last_payment_date = end($quota_dates)['date'];

            // Generar número aleatorio entre 1 y 1000 con formato de 3 dígitos
            $random_number = str_pad(rand(1, 1000), 3, '0', STR_PAD_LEFT);
            $contract->number_contract = 'C001' . '-' . $random_number . '-' . $date->format('dmY');
            $contract->save();


            if ($request->client_type == 'Grupo') {
                // Distribuir cuotas por cada miembro del grupo
                $members = json_decode($contract->people, true);
                $quotaCount = count($quota_dates);
                $totalRequested = floatval(str_replace(',', '.', $request->requested_amount));

                foreach ($members as $index => $member) {
                    $memberRequested = isset($member['quotes']) ? floatval(str_replace(',', '.', $member['quotes'])) : 0;

                    // Calcular el monto a pagar por este miembro (proporcional al monto solicitado)
                    if ($totalRequested > 0) {
                        $memberPayable = ($payable_amount * ($memberRequested / $totalRequested));
                    } else {
                        $memberPayable = $payable_amount / max(1, count($members));
                    }

                    // Calcular cuota por semana: monto a pagar del miembro / número de cuotas
                    $memberQuota = $quotaCount > 0 ? $memberPayable / $quotaCount : 0;
                    
                    // Redondear hacia arriba a 1 decimal (0.10)
                    $memberQuota = ceil($memberQuota * 10) / 10;

                    // Crear las cuotas con el mismo monto para cada semana
                    foreach ($quota_dates as $quota_date) {
                        Quota::create([
                            'contract_id' => $contract->id,
                            'person_document' => $member['document'] ?? null,
                            'person_name' => $member['name'] ?? null,
                            'number' => $quota_date['number'],
                            'amount' => $memberQuota,
                            'debt' => $memberQuota,
                            'date' => $quota_date['date'],
                        ]);
                    }
                }
            } else {
                // Cliente individual: crear cuotas totales como antes
                foreach ($quota_dates as $quota_date) {
                    Quota::create([
                        'contract_id' => $contract->id,
                        'number' => $quota_date['number'],
                        'amount' => $quota,
                        'debt' => $quota,
                        'date' => $quota_date['date'],
                    ]);
                }
            }

            DB::table('config')->update([
                'number_pagare' => $number_pagare
            ]);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            // Log del error para debugging
            Log::error('Error al guardar contrato: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'Error al guardar el contrato: ' . $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Contract $contract)
    {
        return response()->json($contract);
    }

    public function update(Request $request, Contract $contract) {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'nullable|integer|exists:users,id',
            'number_pagare' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $found_number = Contract::where('deleted',0)
        ->where('number_pagare',$request->number_pagare)
        ->exists();

        if($found_number){
            return response()->json([
                'status' => false,
                'error' => 'Ya existe un contrato con ese número de pagaré'
            ]);
        }

        $data = [];

        if ($request->filled('seller_id')) {
            $data['seller_id'] = $request->seller_id;
        }

        if ($request->filled('number_pagare')) {
            $data['number_pagare'] = $request->number_pagare;
        }

        if (!empty($data)) {
            $contract->update($data);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Contract $contract)
    {
        $contract->update([
            'deleted' => 1
        ]);

        return response()->json([
            'status' => true
        ]);
    }

    public function api(Request $request)
    {
        $user = auth()->user();
        $contracts = Contract::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('seller', function ($q) use ($user) {
                    $q->where('credit_manager_id', $user->id);
                });
            })
            ->where(function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('group_name', 'like', '%' . $request->q . '%');
            })->where('paid', 0)->orderBy('name')->orderBy('group_name')->orderBy('date')->get();

        return response()->json(['items' => $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'client_type' => $contract->client_type,
                'name' => $contract->name,
                'group_name' => $contract->group_name,
                'requested_amount' => $contract->requested_amount,
                'date' => $contract->date->format('d/m/Y'),
            ];
        })]);
    }

    public function pdf(Request $request, Contract $contract)
    {
        $fpdf = new PdfModel('P');

        $fpdf->AddPage();

        $fpdf->AddFont('Montserrat', '');
        $fpdf->AddFont('Montserrat', 'B');

        $fpdf->SetFont('Montserrat', 'B', 14);

        $fpdf->Image(asset('assets/images/logo.png'), 160, 20, 20);
        $fpdf->Cell(190, 10, utf8_decode('CONTRATO DEL PRÉSTAMO'), 0, 1, 'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 12);

        if ($contract->client_type == 'Personal') {

            $fpdf->Cell(190, 10, utf8_decode('Cliente: ' . $contract->name), 0, 1);

            $fpdf->Cell(190, 10, utf8_decode('DNI: ' . $contract->document), 0, 1);

            $fpdf->Cell(190, 10, utf8_decode('Dirección: ' . $contract->address), 0, 1);
        } elseif ($contract->client_type == 'Grupo') {

            $fpdf->Cell(190, 10, utf8_decode('Cliente: ' . $contract->group_name . ', conformado por:'), 0, 1);

            $people = json_decode($contract->people);

            foreach ($people as $client) {
                $fpdf->MultiCell(190, 5, utf8_decode('- ' . $client->document . ' / ' . $client->name . ' / ' . $client->address), 0, 1);
            }
        }


        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 12);

        $fpdf->Cell(190, 10, utf8_decode('MONTO Y CONDICIONES DEL PRÉSTAMO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('1. Se acuerda prestar al Cliente la cantidad de ' . $contract->requested_amount . ' nuevos soles. con un interés del ' . $contract->percentage . ' %'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('2. El plazo del préstamo será de ' . $contract->months_number . ' mes(es), comenzando el ' . $contract->date->format('d/m/Y')), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('3. El Cliente se compromete a cancelar la totalidad del préstamo sumando el interés acordado, en la cantidad de ' . $contract->quotas_number . ' cuotas semanales de ' . $contract->quota_amount . ' nuevos soles cada una, a partir del ' . $contract->first_payment_date->format('d/m/Y')), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('4. El cronograma de pagos será el siguiente:'), 0, 1);

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 10);

        $fpdf->Cell(35, 6);
        $fpdf->Cell(40, 6, utf8_decode('NÚMERO'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('CUOTA'), 1, 0, 'C');
        $fpdf->Cell(40, 6, utf8_decode('FECHA'), 1, 0, 'C');

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', '', 10);

        foreach ($contract->quotas as $quota) {
            $fpdf->Cell(35, 6);
            $fpdf->Cell(40, 6, utf8_decode($quota->number), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode($quota->amount), 1, 0, 'C');
            $fpdf->Cell(40, 6, utf8_decode($quota->date->format('d/m/Y')), 1, 0, 'C');

            $fpdf->Ln();
        }

        $fpdf->Ln();

        $fpdf->SetFont('Montserrat', 'B', 12);

        $fpdf->Cell(190, 10, utf8_decode('FORMA DE PAGO'), 0, 1);

        $fpdf->SetFont('Montserrat', '', 12);
        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('1. Los pagos deberán realizarse puntualmente en las fechas acordadas. Cada día de retraso, quedará evidenciado en el histórico del cliente y esto afectará un préstamo futuro.'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(10, 5);
        $fpdf->MultiCell(180, 5, utf8_decode('2. Los pagos se realizan en los canales establecidos y explicados anteriormente: Efectivo, depósito en cuenta del Banco de la Nación, Caja Piura y BCP; así como mediante Yape y Plin.'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(180, 5, utf8_decode('El presente contrato se está firmando el día ' . $contract->date->isoFormat('D [de] MMMM [de] YYYY') . '.'), 0, 1);

        $fpdf->Ln();

        $fpdf->Cell(180, 5, utf8_decode('Piura, Perú.'), 0, 1);

        $fpdf->Output('D', 'contrato_' . $contract->id . '.pdf');
        exit();
    }

    public function pdf1Blade(Contract $contract)
    {
        // Preparar las variables necesarias
        $amount = number_format($contract->requested_amount ?? 0, 2);
        $requestedInWords = \App\Helpers\NumberToWords::convertir($contract->requested_amount ?? 0, 'SOLES');
        $amountInWords = \App\Helpers\NumberToWords::convertir($contract->payable_amount ?? 0, 'SOLES');
        $payable_amount = number_format($contract->payable_amount ?? 0, 2);

        $pdf = PDF::loadView('contracts.pdf1', compact('contract', 'amount', 'amountInWords', 'payable_amount', 'requestedInWords'));
        $fileName = 'PDFP-' . $contract->id . '-' . $contract->date->format('dmY') . '.pdf';
        return $pdf->download($fileName);
    }

    public function pdf2Blade(Contract $contract)
    {
        // Preparar las variables necesarias
        $amount = number_format($contract->requested_amount ?? 0, 2);
        $requestedInWords = \App\Helpers\NumberToWords::convertir($contract->requested_amount ?? 0, 'SOLES');
        $amountInWords = \App\Helpers\NumberToWords::convertir($contract->requested_amount ?? 0, 'SOLES');
        $payable_amount = number_format($contract->payable_amount ?? 0, 2);

        $pdf = PDF::loadView('contracts.pdf2', compact('contract', 'amount', 'amountInWords', 'payable_amount', 'requestedInWords'));
        $fileName = 'PDFPA-' . $contract->id . '-' . $contract->date->format('dmY') . '.pdf';
        return $pdf->download($fileName);
    }

    public function pdf3Blade(Contract $contract)
    {
        Carbon::setLocale('es');

        $company = 'APLICANDO CONFIANZA PERU S.A.C';
        $ruc = '20611409355';
        $people = json_decode($contract->people);
        $numberWeeks = $contract->quotas_number ?? '';
        $interestRate = $contract->percentage ?? 0;
        $payable_amount = $contract->payable_amount ?? 0;
        $amount = number_format($contract->requested_amount ?? 0, 2);

        // Agregar conversión a letras
        $amountInWords = \App\Helpers\NumberToWords::convertir($contract->requested_amount ?? 0, 'SOLES');
        $requestedInWords = \App\Helpers\NumberToWords::convertir($contract->requested_amount ?? 0, 'SOLES');
        $contractDate = $contract->date
            ? Carbon::parse($contract->date)->format('j \d\e F \d\e Y')
            : '________';
        $quotas = $contract->quotas;

        $pdf = PDF::loadView('contracts.pdf3', compact('contract', 'company', 'ruc', 'people', 'amount', 'amountInWords', 'contractDate', 'quotas', 'numberWeeks', 'interestRate', 'payable_amount', 'requestedInWords'));

        $fileName = 'PDFPA-' . $contract->id . '-' . $contract->date->format('dmY') . '.pdf';
        return $pdf->download($fileName);
    }
}
