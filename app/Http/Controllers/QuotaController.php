<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
            ->when($request->paid !== null && $request->paid !== '', function ($query) use ($request) {
                return $query->where('paid', $request->paid);
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
        if ($request->input('detail_mode') === 'portfolio') {
            return $this->portfolioQuotaReport($request, $contract);
        }

        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->as_of)->toDateString()
            : null;

        $quotas = DB::table('quotas')
            ->leftJoin('payments', function ($join) use ($asOf) {
                $join->on('payments.quota_id', '=', 'quotas.id')
                    ->where('payments.deleted', 0);

                if ($asOf) {
                    $join->whereDate('payments.date', '<=', $asOf);
                }
            })
            ->where('quotas.contract_id', $request->contract_id)
            ->groupBy(
                'quotas.id',
                'quotas.number',
                'quotas.date',
                'quotas.amount',
                'quotas.person_document',
                'quotas.person_name'
            )
            ->selectRaw("
                quotas.id,
                quotas.number,
                quotas.date,
                quotas.amount,
                quotas.person_document,
                quotas.person_name,
                GREATEST(quotas.amount - COALESCE(SUM(payments.amount), 0), 0) as debt
            ")
            ->havingRaw('GREATEST(quotas.amount - COALESCE(SUM(payments.amount), 0), 0) > 0.009')
            ->orderBy('quotas.number', 'asc')
            ->get()
            ->map(function ($quota) {
                $quota->date = $quota->date ? Carbon::parse($quota->date) : null;
                return $quota;
            });
        
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

    public function payments(Quota $quota)
    {
        $quota->load(['contract.seller', 'payments' => function ($query) {
            $query->active()
                ->with('payment_method')
                ->latest('date')
                ->latest('id');
        }]);

        $contract = $quota->contract;
        $payments = $quota->payments->map(function ($payment) {
            $methodName = optional($payment->payment_method)->name ?? 'N/A';
            if ((int) optional($payment->payment_method)->id === 1 || strtoupper($methodName) === 'EFECTIVO') {
                $methodName = 'Retanqueo';
            }

            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'date' => $payment->date ? $payment->date->format('d/m/Y') : null,
                'due_days' => $payment->due_days,
                'payment_method' => $methodName,
                'image_url' => $payment->image ? asset('storage/' . $payment->image) : null,
            ];
        })->values();

        return response()->json([
            'quota' => [
                'id' => $quota->id,
                'number' => $quota->number,
                'amount' => (float) $quota->amount,
                'debt' => (float) $quota->debt,
                'date' => $quota->date ? $quota->date->format('d/m/Y') : null,
                'person_name' => $quota->person_name,
                'person_document' => $quota->person_document,
                'client' => $contract ? $contract->client() : 'N/A',
                'seller_name' => optional($contract?->seller)->name,
            ],
            'payments' => $payments,
            'payments_total' => round((float) $payments->sum('amount'), 2),
        ]);
    }

    private function portfolioQuotaReport(Request $request, Contract $contract)
    {
        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->as_of)->toDateString()
            : null;

        $quotas = Quota::where('contract_id', $contract->id)
            ->with(['payments' => function ($query) use ($asOf) {
                $query->active()
                    ->when($asOf, fn($q) => $q->whereDate('date', '<=', $asOf))
                    ->with('payment_method')
                    ->orderBy('date', 'asc')
                    ->orderBy('id', 'asc');
            }])
            ->orderBy('number', 'asc')
            ->orderBy('person_name', 'asc')
            ->get();

        $quotaRows = [];
        $memberSummary = [];
        $paymentsHistory = [];

        foreach ($quotas as $quota) {
            $paidToCutoff = round((float) $quota->payments->sum('amount'), 2);
            $debt = round(max((float) $quota->amount - $paidToCutoff, 0), 2);
            $memberKey = trim(($quota->person_document ?? '') . '|' . ($quota->person_name ?? ''));
            $memberLabel = trim($quota->person_name ?: ($contract->name ?: $contract->group_name ?: 'Sin nombre'));
            $memberDocument = $quota->person_document;

            if (!isset($memberSummary[$memberKey])) {
                $memberSummary[$memberKey] = [
                    'document' => $memberDocument,
                    'name' => $memberLabel,
                    'total_amount' => 0,
                    'paid_total' => 0,
                    'debt_total' => 0,
                    'paid_quotas' => [],
                    'pending_quotas' => [],
                ];
            }

            $memberSummary[$memberKey]['total_amount'] += (float) $quota->amount;
            $memberSummary[$memberKey]['paid_total'] += $paidToCutoff;
            $memberSummary[$memberKey]['debt_total'] += $debt;

            if ($debt > 0.009) {
                $memberSummary[$memberKey]['pending_quotas'][] = $quota->number;
            } else {
                $memberSummary[$memberKey]['paid_quotas'][] = $quota->number;
            }

            $paymentRows = [];
            foreach ($quota->payments as $payment) {
                $methodName = optional($payment->payment_method)->name ?? 'N/A';
                if ((int) optional($payment->payment_method)->id === 1 || strtoupper($methodName) === 'EFECTIVO') {
                    $methodName = 'Retanqueo';
                }

                $paymentItem = [
                    'id' => $payment->id,
                    'quota_id' => $quota->id,
                    'quota_number' => $quota->number,
                    'member_name' => $memberLabel,
                    'member_document' => $memberDocument,
                    'amount' => (float) $payment->amount,
                    'date' => $payment->date ? $payment->date->format('d/m/Y') : null,
                    'due_days' => $payment->due_days,
                    'payment_method' => $methodName,
                    'image_url' => $payment->image ? asset('storage/' . $payment->image) : null,
                ];

                $paymentRows[] = $paymentItem;
                $paymentsHistory[] = $paymentItem;
            }

            $quotaRows[] = [
                'quota_id' => $quota->id,
                'number' => $quota->number,
                'date' => $quota->date ? $quota->date->format('d/m/Y') : null,
                'member_name' => $memberLabel,
                'member_document' => $memberDocument,
                'amount' => (float) $quota->amount,
                'paid_total' => $paidToCutoff,
                'debt' => $debt,
                'status' => $debt > 0.009 ? 'Pendiente' : 'Pagado',
                'payments' => $paymentRows,
            ];
        }

        if ($contract->client_type === 'Grupo') {
            $quotaRows = collect($quotaRows)
                ->groupBy('number')
                ->map(function ($group) {
                    $first = $group->first();

                    return [
                        'number' => $first['number'],
                        'date' => $first['date'],
                        'amount' => round((float) collect($group)->sum('amount'), 2),
                        'paid_total' => round((float) collect($group)->sum('paid_total'), 2),
                        'debt' => round((float) collect($group)->sum('debt'), 2),
                        'status' => collect($group)->sum('debt') > 0.009 ? 'Pendiente' : 'Pagado',
                        'members' => collect($group)->map(function ($item) {
                            return [
                                'name' => $item['member_name'],
                                'document' => $item['member_document'],
                                'amount' => $item['amount'],
                                'paid_total' => $item['paid_total'],
                                'debt' => $item['debt'],
                                'status' => $item['status'],
                                'payments' => $item['payments'],
                            ];
                        })->values()->all(),
                        'payments' => collect($group)->flatMap(fn($item) => $item['payments'])->sortByDesc(function ($payment) {
                            return Carbon::createFromFormat('d/m/Y', $payment['date'] ?? now()->format('d/m/Y'))->timestamp;
                        })->values()->all(),
                    ];
                })
                ->values()
                ->all();
        } else {
            $quotaRows = collect($quotaRows)->map(function ($item) {
                return [
                    'number' => $item['number'],
                    'date' => $item['date'],
                    'amount' => $item['amount'],
                    'paid_total' => $item['paid_total'],
                    'debt' => $item['debt'],
                    'status' => $item['status'],
                    'members' => [[
                        'name' => $item['member_name'],
                        'document' => $item['member_document'],
                        'amount' => $item['amount'],
                        'paid_total' => $item['paid_total'],
                        'debt' => $item['debt'],
                        'status' => $item['status'],
                        'payments' => $item['payments'],
                    ]],
                    'payments' => $item['payments'],
                ];
            })->values()->all();
        }

        $memberSummary = collect($memberSummary)
            ->map(function ($member) {
                $member['total_amount'] = round((float) $member['total_amount'], 2);
                $member['paid_total'] = round((float) $member['paid_total'], 2);
                $member['debt_total'] = round((float) $member['debt_total'], 2);
                $member['paid_quotas'] = array_values(array_unique($member['paid_quotas']));
                $member['pending_quotas'] = array_values(array_unique($member['pending_quotas']));
                return $member;
            })
            ->sortBy('name')
            ->values()
            ->all();

        $paymentsHistory = collect($paymentsHistory)
            ->sortByDesc(function ($payment) {
                return Carbon::createFromFormat('d/m/Y', $payment['date'] ?? now()->format('d/m/Y'))->timestamp;
            })
            ->values()
            ->all();

        $summary = [
            'quotas_total' => round((float) $quotas->sum('amount'), 2),
            'paid_total' => round((float) collect($quotaRows)->sum('paid_total'), 2),
            'debt_total' => round((float) collect($quotaRows)->sum('debt'), 2),
            'quotas_count' => count($quotaRows),
            'paid_quotas_count' => collect($quotaRows)->filter(fn($quota) => (float) $quota['debt'] <= 0.009)->count(),
            'pending_quotas_count' => collect($quotaRows)->filter(fn($quota) => (float) $quota['debt'] > 0.009)->count(),
            'members_count' => count($memberSummary),
            'payments_count' => count($paymentsHistory),
            'as_of' => $asOf,
        ];

        return response()->json([
            'contract' => [
                'id' => $contract->id,
                'client_type' => $contract->client_type,
                'name' => $contract->name,
                'group_name' => $contract->group_name,
                'number_pagare' => $contract->number_pagare,
            ],
            'summary' => $summary,
            'quotas' => $quotaRows,
            'members' => $memberSummary,
            'payments' => $paymentsHistory,
        ]);
    }
}
