<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\QuotasExport;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;

class QuotaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $selectedClient = null;
        if ($request->client_id) {
            $selectedClient = Contract::active()->find($request->client_id);
        }

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
            ->when($request->credit_manager_id, function ($query, $creditManagerId) {
                return $query->whereHas('contract.seller', function ($q) use ($creditManagerId) {
                    return $q->where('credit_manager_id', $creditManagerId);
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
                    ->with('payment_method')
                    ->latest('date')
                    ->latest('id');
            }])
            ->latest('date')
            ->latest('id')
            ->paginate(20);

        $credit_managers = User::where('role', 'credit_manager')->active()->get();

        $sellers = User::seller()->where('state', 0)->active()
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->where('credit_manager_id', $user->id);
            })
            ->when($request->credit_manager_id, function ($query, $creditManagerId) {
                return $query->where('credit_manager_id', $creditManagerId);
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('quotas.index', compact('quotas', 'sellers', 'credit_managers', 'selectedClient'));
    }

    public function excel(Request $request)
    {
        $name = "GestionDeCuotas_" . now()->format('d_m_Y') . ".xlsx";
        return Excel::download(new QuotasExport, $name);
    }

    public function clients(Request $request)
    {
        $user = auth()->user();
        $q = trim($request->q ?? '');
        if ($q === '') {
            return response()->json(['items' => []]);
        }

        $contracts = Contract::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($user->hasRole('credit_manager'), function ($query) use ($user) {
                return $query->whereHas('seller', function ($q) use ($user) {
                    $q->where('credit_manager_id', $user->id);
                });
            })
            ->when($request->credit_manager_id, function ($query, $creditManagerId) {
                return $query->whereHas('seller', function ($q) use ($creditManagerId) {
                    $q->where('credit_manager_id', $creditManagerId);
                });
            })
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('group_name', 'like', '%' . $q . '%')
                    ->orWhereHas('quotas', function ($q2) use ($q) {
                        $q2->where('person_name', 'like', '%' . $q . '%');
                    });
            })
            ->latest('date')
            ->limit(20)
            ->get();

        $items = $contracts->map(function ($contract) {
            $label = $contract->client_type === 'Personal'
                ? ($contract->name . ' - ' . $contract->document)
                : $contract->group_name;

            return [
                'id' => $contract->id,
                'text' => $label,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function api(Request $request){
        $contract = Contract::findOrFail($request->contract_id);
        $quotas = Quota::where('contract_id', $request->contract_id)
            ->where('paid', 0)
            ->orderBy('number', 'asc')
            ->get();
        
        if ($contract->client_type == 'Grupo') {
            // Agrupar cuotas por número para grupos
            $groupedQuotas = $quotas->groupBy('number')->map(function($quotaGroup) {
                $firstQuota = $quotaGroup->first();
                $totalAmount = $quotaGroup->sum('amount');
                $totalDebt = $quotaGroup->sum('debt');
                
                $people = $quotaGroup->map(function($quota) {
                    return [
                        'quota_id' => $quota->id,
                        'document' => $quota->person_document,
                        'name' => $quota->person_name,
                        'amount' => $quota->amount,
                        'debt' => $quota->debt,
                    ];
                })->values();
                
                return [
                    'number' => $firstQuota->number,
                    'date' => $firstQuota->date->format('d/m/Y'),
                    'amount' => $totalAmount,
                    'debt' => $totalDebt,
                    'people' => $people
                ];
            })->values();
            
            return response()->json([
                'contract' => $contract,
                'quotas' => $groupedQuotas
            ]);
        } else {
            // Para individuales, mantener el flujo original
            $quotas = $quotas->map(function($quota){
                return [
                    'id' => $quota->id,
                    'number' => $quota->number,
                    'date' => $quota->date->format('d/m/Y'),
                    'amount' => $quota->amount,
                    'debt' => $quota->debt,
                ];
            });

            return response()->json([
                'contract' => $contract,
                'quotas' => $quotas
            ]);
        }
    }
}
