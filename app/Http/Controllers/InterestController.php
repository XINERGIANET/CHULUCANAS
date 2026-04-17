<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Contract;
use App\Models\Quota;
use Svg\Tag\Rect;
use Symfony\Component\VarDumper\Caster\FrameStub;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SBSExport;
use Carbon\Carbon;

class InterestController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month;
        $year = $request->year ?? date('Y');

        $clients = Contract::active()->when($request->name, function ($query, $name) {
            return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
        })->when($request->start_date, function ($query, $start_date) {
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function ($query, $end_date) {
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id')->paginate(20);

        $clients->getCollection()->transform(function ($contract) use ($month, $year) {
            $contractInterest = (float) ($contract->interest);

            // Para GRUPOS: contar el total de cuotas reales en la BD
            if ($contract->client_type == 'Grupo') {
                $totalQuotas = Quota::where('contract_id', $contract->id)->count();

                // Interés por cuota (basado en el total real de cuotas)
                $interestPerQuota = $totalQuotas > 0 ? ($contractInterest / $totalQuotas) : 0;
            } else {
                // Para PERSONALES: usar quotas_number del contrato
                $quotasNumber = (int) ($contract->quotas_number);
                $interestPerQuota = $quotasNumber > 0 ? ($contractInterest / $quotasNumber) : 0;
            }

            // Contar cuotas en el mes/año seleccionado
            if ($month) {
                $quotasCount = Quota::where('contract_id', $contract->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->count();
            } else {
                $quotasCount = Quota::where('contract_id', $contract->id)
                    ->whereYear('date', $year)
                    ->count();
            }

            // Interés total filtrado
            $contract->filtered_interest = $interestPerQuota * $quotasCount;

            return $contract;
        });

        return view('interests.index', compact('clients'));
    }

    public function store(Request $request)
    {
        // 
    }

    public function edit(Request $request, Transfer $transfer)
    {
        // 
    }

    public function update(Request $request, Transfer $transfer)
    {
        // 
    }

    public function destroy(Request $request, Transfer $transfer)
    {
        //    
    }

    public function report_sbs(Request $request)
    {
        return view('interests.sbs');
    }

    
    public function download_sbs(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : null;
        $year  = $request->filled('year')  ? (int) $request->year  : (int) date('Y');

        $reportMonth = $month
            ? sprintf('%04d/%02d', $year, $month)   // 2025/09
            : sprintf('%04d', $year);


        $contracts = Contract::active()     // => deleted = 0 (por tu scope)
            ->where('paid', 0)              // solo contratos no pagados
            ->with([
                'district.province.department',
                'quotas' => fn($q) => $q->orderBy('date'), // precargar cuotas
                'quotas.payments' // precargar pagos para evitar consultas N+1
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        
        $filename = $month
            ? sprintf("MIC_20611409355_%04d%02d.xlsx", $year, $month)
            : sprintf("MIC_20611409355_%04d.xlsx", $year);
        
        return Excel::download(new SBSExport($contracts, $reportMonth), $filename);
    }
}
