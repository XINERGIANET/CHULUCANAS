<?php

namespace App\Exports;

use App\Models\Quota;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class DuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = auth()->user();
        $request = request();
        $referenceDate = $request->date ? Carbon::parse($request->date)->toDateString() : now()->toDateString();

        $quotas = Quota::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('contract', function($query) use($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('contract', function($query) use($name){
                return $query->where(function($query) use ($name){
                    return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
                });
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->from_days, function($query, $from_days) use ($referenceDate){
            return $query->whereRaw('DATEDIFF(?, date) >= ?', [$referenceDate, $from_days]);
        })->when($request->to_days, function($query, $to_days) use ($referenceDate){
            return $query->whereRaw('DATEDIFF(?, date) <= ?', [$referenceDate, $to_days]);
        })->where('paid', 0)
          ->whereDate('date', '<', $referenceDate)
          ->orderBy('date')->get();

        return $quotas;

    }

    public function map($quota): array
    {
        $request = request();
        $refDate = $request->date ? Carbon::parse($request->date) : now();
        return [
            optional(optional($quota->contract)->seller)->name,
            optional($quota->contract)->client(),
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date->format('d/m/Y'),
            ($quota->date->lt($refDate) ? $refDate->diffInDays($quota->date) : 0)
        ];
    }

    public function headings(): array
    {
        return [
            'Asesor',
            'Cliente',
            'Número de cuota',
            'Monto',
            'Saldo',
            'Fecha de pago',
            'Días de mora'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
