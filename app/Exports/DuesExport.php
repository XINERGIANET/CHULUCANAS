<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder */
    protected $quotasQuery;

    /** @var string */
    protected $referenceDate;

    public function __construct($quotasQuery, string $referenceDate = null)
    {
        $this->quotasQuery   = $quotasQuery;
        $this->referenceDate = $referenceDate ?? now()->toDateString();
    }

    /**
     * Misma base que PaymentController::dues() (duesQuotasBaseQuery).
     * Agrupa por contract_id + number para no mezclar cuotas de distintos contratos.
     */
    public function collection()
    {
        return (clone $this->quotasQuery)->get();
    }

    public function map($quota): array
    {
        $refDate = Carbon::parse($this->referenceDate);
        $diasMora = $quota->date->lt($refDate) ? $refDate->diffInDays($quota->date) : 0;

        return [
            optional(optional(optional($quota->contract)->seller)->creditManager)->name,
            optional(optional($quota->contract)->seller)->name,
            $quota->contract_number_pagare ?? optional($quota->contract)->number_pagare,
            optional($quota->contract)->client(),
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date->format('d/m/Y'),
            $diasMora,
        ];
    }

    public function headings(): array
    {
        return [
            'Jefe de credito',
            'Asesor',
            'N° Pagaré',
            'Cliente',
            'Número de cuota',
            'Monto',
            'Saldo',
            'Fecha de pago',
            'Días de mora',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
