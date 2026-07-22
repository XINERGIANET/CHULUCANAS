<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InterestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $clients;

    public function __construct($clients)
    {
        $this->clients = $clients;
    }

    public function collection()
    {
        return $this->clients;
    }

    public function map($client): array
    {
        $name = $client->client_type == 'Personal' ? $client->name : $client->group_name;

        return [
            $client->number_pagare,
            $name,
            number_format($client->filtered_interest ?? 0, 2, '.', ''),
        ];
    }

    public function headings(): array
    {
        return [
            'Número de pagaré',
            'Cliente/Grupo',
            'Interés acumulado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
