<?php

namespace App\Exports;

use App\Models\Quota;
use App\Models\User;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $request = request();
        $user = auth()->user();

        $quotas = Quota::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('contract', function ($q) use ($user) {
                    return $q->where('seller_id', $user->id);
                });
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('contract.seller', function ($q) use ($user) {
                    return $q->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('contract', function ($q) use ($name) {
                    return $q->where(function ($q) use ($name) {
                        return $q->where('name', 'like', '%' . $name . '%')
                            ->orWhere('group_name', 'like', '%' . $name . '%');
                    });
                });
            })
            ->when($request->client_id, function ($query, $clientId) {
                return $query->where('contract_id', $clientId);
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('contract', function ($q) use ($seller_id) {
                    return $q->where('seller_id', $seller_id);
                });
            })
            ->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->with(['contract.seller', 'payments' => function ($query) {
                $query->active()
                    ->latest('date')
                    ->latest('id');
            }])
            ->latest('date')
            ->latest('id')
            ->get();

        return $quotas;
    }

    public function map($quota): array
    {
        $contract = $quota->contract;
        $client = $contract ? $contract->client() : 'N/A';
        $lastPayment = $quota->payments->first();
        $paymentDate = $lastPayment ? $lastPayment->date : null;

        return [
            $client,
            $quota->person_name,
            $quota->person_document,
            optional($contract)->seller->name,
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date ? $quota->date->format('d/m/Y') : '',
            $quota->paid ? 'Pagado' : 'Pendiente',
            $paymentDate ? $paymentDate->format('d/m/Y') : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'Persona',
            'Documento',
            'Asesor C.',
            'N° cuota',
            'Monto',
            'Deuda',
            'Fecha de cuota',
            'Estado',
            'Fecha de pago',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
