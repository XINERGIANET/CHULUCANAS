<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChargesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder */
    protected $quotasQuery;

    public function __construct($quotasQuery)
    {
        $this->quotasQuery = $quotasQuery;
    }

    /**
     * Misma base que PaymentController::charges() (chargesQuotasBaseQuery).
     */
    public function collection()
    {
        return (clone $this->quotasQuery)->orderBy('date')->get();
    }

    public function map($quota): array
    {
        return [
            optional($quota->contract)->client(),
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date->format('d/m/Y'),
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Número de cuota',
            'Monto',
            'Saldo',
            'Fecha de pago',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
