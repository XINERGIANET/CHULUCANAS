<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function dashboard(Request $request, $user): array
    {
        $milestone = $this->date($request->end_date_2 ?: now());

        $filters = [
            'credit_manager_id' => $request->credit_manager_id,
            'seller_id' => $request->seller_id_2,
        ];

        $snapshot = $this->snapshot($milestone, $filters, $user);

        return [
            'cutoff' => $snapshot,
            'evolution' => null,
        ];
    }

    public function cardDetails(Request $request, $user): array
    {
        $card = $request->card;
        $allowed = [
            'gross_portfolio',
            'current_portfolio',
            'arrears_1_120',
            'arrears_over_120',
            'arrears_total',
            'arrears_percent',
            'active_clients',
            'clients_over_120',
            'individual_group_clients',
            'finished_clients_with_arrears_1_120',
            'disbursed_amount',
            'pending_quotas_count',
            'evolution_initial',
            'evolution_increments',
            'evolution_reductions',
            'evolution_final',
        ];

        if (!in_array($card, $allowed, true)) {
            return [
                'status' => false,
                'error' => 'Tipo de tarjeta invalido',
            ];
        }

        $milestone = $this->date($request->end_date_2 ?: now());
        $asOf = $milestone->toDateString();
        $filters = [
            'credit_manager_id' => $request->credit_manager_id,
            'seller_id' => $request->seller_id_2,
        ];

        if (in_array($card, ['evolution_initial', 'evolution_increments', 'evolution_reductions', 'evolution_final'], true)) {
            if (!$request->start_date_2) {
                return [
                    'status' => true,
                    'type' => 'summary',
                    'total' => 0,
                    'items' => [[
                        'concept' => 'Evolucion no calculada',
                        'detail' => 'Seleccione una fecha desde para ver el detalle diario.',
                        'amount' => 0,
                    ]],
                ];
            }

            $evolution = $this->evolution($this->date($request->start_date_2), $cutoff, $filters, $user);

            if ($card === 'evolution_initial') {
                return [
                    'status' => true,
                    'type' => 'summary',
                    'total' => 1,
                    'items' => [[
                        'concept' => 'Saldo inicial',
                        'detail' => $evolution['start_date'],
                        'amount' => $evolution['initial_balance'],
                    ]],
                ];
            }

            $items = collect($evolution['daily']);
            if ($card === 'evolution_increments') {
                $items = $items->filter(fn($row) => (float) $row['increments'] > 0);
            } elseif ($card === 'evolution_reductions') {
                $items = $items->filter(fn($row) => ((float) $row['payments'] + (float) $row['deteriorated_over_120']) > 0);
            }

            return [
                'status' => true,
                'type' => 'evolution',
                'total' => $items->count(),
                'items' => $items->values(),
            ];
        }

        if ($card === 'disbursed_amount') {
            $items = $this->contractsQuery($asOf, $filters, $user)
                ->select(
                    'contracts.number_pagare',
                    'contracts.client_type',
                    'contracts.name',
                    'contracts.group_name',
                    'contracts.requested_amount',
                    'contracts.interest',
                    'contracts.payable_amount',
                    DB::raw("DATE_FORMAT(contracts.date, '%d/%m/%Y') as date"),
                    'users.name as seller_name'
                )
                ->orderBy('contracts.date', 'desc')
                ->get();

            return [
                'status' => true,
                'type' => 'contracts',
                'total' => $items->count(),
                'items' => $items,
            ];
        }

        if ($card === 'finished_clients_with_arrears_1_120') {
            $items = $this->contractsQuery($asOf, $filters, $user)
                ->where('contracts.paid', 1)
                ->whereExists(function ($query) use ($asOf) {
                    $query->select(DB::raw(1))
                        ->from('quotas')
                        ->join('payments', 'payments.quota_id', '=', 'quotas.id')
                        ->whereColumn('quotas.contract_id', 'contracts.id')
                        ->where('payments.deleted', 0)
                        ->whereDate('payments.date', '<=', $asOf)
                        ->whereBetween('payments.due_days', [1, 120]);
                })
                ->select(
                    'contracts.number_pagare',
                    'contracts.client_type',
                    'contracts.name',
                    'contracts.group_name',
                    'contracts.requested_amount',
                    DB::raw("DATE_FORMAT(contracts.date, '%d/%m/%Y') as date"),
                    'users.name as seller_name',
                    DB::raw("(SELECT MAX(payments.due_days)
                              FROM quotas
                              JOIN payments ON payments.quota_id = quotas.id
                              WHERE quotas.contract_id = contracts.id
                                AND payments.deleted = 0
                                AND DATE(payments.date) <= '{$asOf}'
                                AND payments.due_days BETWEEN 1 AND 120) as max_due_days")
                )
                ->orderBy('contracts.date', 'desc')
                ->get();

            return [
                'status' => true,
                'type' => 'contracts',
                'total' => $items->count(),
                'items' => $items,
            ];
        }

        if (in_array($card, ['active_clients', 'clients_over_120', 'individual_group_clients'], true)) {
            $query = $this->contractBalanceDetailsQuery($asOf, $filters, $user);

            if ($card === 'active_clients') {
                $query->havingRaw('COALESCE(client_counts.client_count, 0) > 0');
            } elseif ($card === 'clients_over_120') {
                $query->havingRaw("SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END) > 0.009", [$asOf]);
            }

            $items = $query->get()->map(function ($item) use ($card) {
                $item->client_count = (int) ($card === 'clients_over_120'
                    ? ($item->client_count_over_120 ?? 0)
                    : ($item->client_count ?? 0));

                return $item;
            });

            return [
                'status' => true,
                'type' => 'clients',
                'total' => (int) $items->sum('client_count'),
                'items' => $items,
            ];
        }

        if ($card === 'gross_portfolio') {
            $items = $this->quotaBalanceDetailsQuery($asOf, $filters, $user, false)->get();

            return [
                'status' => true,
                'type' => 'quotas',
                'total' => $items->count(),
                'items' => $items,
            ];
        }

        $afterMilestoneOnly = $card === 'current_portfolio';
        $query = $this->quotaBalanceDetailsQuery($asOf, $filters, $user, $afterMilestoneOnly);

        if ($card === 'current_portfolio') {
            $query->whereRaw('DATEDIFF(?, q.quota_date) <= 120', [$asOf]);
        } elseif ($card === 'arrears_1_120' || $card === 'arrears_percent') {
            $query->whereRaw('DATEDIFF(?, q.quota_date) BETWEEN 1 AND 120', [$asOf]);
        } elseif ($card === 'arrears_over_120') {
            $query->whereRaw('DATEDIFF(?, q.quota_date) > 120', [$asOf]);
        } elseif ($card === 'arrears_total') {
            $query->whereRaw('DATEDIFF(?, q.quota_date) > 0', [$asOf]);
        }

        $items = $query->get();

        return [
            'status' => true,
            'type' => 'quotas',
            'total' => $items->count(),
            'items' => $items,
        ];
    }

    public function snapshot(Carbon $cutoff, array $filters, $user = null): array
    {
        $asOf = $cutoff->toDateString();
        $rowsGross = DB::query()->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, false), 'q');
        $rowsAfterMilestone = DB::query()->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, true), 'q');

        $gross = (float) (clone $rowsGross)
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->sum(DB::raw('q.amount - q.paid_to_cutoff'));

        $arrearsTotals = (clone $rowsGross)
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) BETWEEN 1 AND 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as arrears_1_120,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as arrears_over_120
            ", [$asOf, $asOf])
            ->first();

        $amountTotals = (clone $rowsAfterMilestone)
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->selectRaw("
                COALESCE(SUM(q.amount - q.paid_to_cutoff), 0) as portfolio_after_milestone,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as arrears_over_120_post_hito,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) <= 0 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as current_installments
            ", [$asOf, $asOf])
            ->first();

        $clientTotals = DB::query()
            ->fromSub($this->clientSnapshotQuery($asOf, $filters, $user), 'c')
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 <= 0.009 THEN c.client_key END) as active_clients,
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 > 0.009 THEN c.client_key END) as clients_over_120,
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 <= 0.009 AND c.client_type = 'Personal' THEN c.client_key END) as individual_clients,
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 <= 0.009 AND c.client_type = 'Grupo' THEN c.client_key END) as group_clients
            ")
            ->first();

        $disbursed = $this->contractsQuery($asOf, $filters, $user)
            ->sum('contracts.requested_amount');

        $finishedWithArrears = DB::query()
            ->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, true), 'q')
            ->join('payments', 'payments.quota_id', '=', 'q.quota_id')
            ->where('payments.deleted', 0)
            ->whereDate('payments.date', '<=', $asOf)
            ->whereBetween('payments.due_days', [1, 120])
            ->select('q.contract_id')
            ->groupBy('q.contract_id')
            ->havingRaw('SUM(q.amount - q.paid_to_cutoff) <= 0.009')
            ->get()
            ->count();

        $arrears1To120 = (float) ($arrearsTotals->arrears_1_120 ?? 0);
        $arrearsOver120 = (float) ($arrearsTotals->arrears_over_120 ?? 0);
        $portfolioAfterMilestone = (float) ($amountTotals->portfolio_after_milestone ?? 0);
        $currentPortfolio = max(0, $portfolioAfterMilestone - (float) ($amountTotals->arrears_over_120_post_hito ?? 0));
        $portfolioForPercent = max(0, $gross - $arrearsOver120);

        return [
            'gross_portfolio' => round($gross, 2),
            'current_portfolio' => round($currentPortfolio, 2),
            'current_installments' => round((float) ($amountTotals->current_installments ?? 0), 2),
            'arrears_1_120' => round($arrears1To120, 2),
            'arrears_over_120' => round($arrearsOver120, 2),
            'arrears_total' => round($arrears1To120 + $arrearsOver120, 2),
            'arrears_percent' => $portfolioForPercent > 0 ? round(($arrears1To120 / $portfolioForPercent) * 100, 2) : 0,
            'active_clients' => (int) ($clientTotals->active_clients ?? 0),
            'clients_over_120' => (int) ($clientTotals->clients_over_120 ?? 0),
            'individual_clients' => (int) ($clientTotals->individual_clients ?? 0),
            'group_clients' => (int) ($clientTotals->group_clients ?? 0),
            'finished_clients_with_arrears_1_120' => $finishedWithArrears,
            'disbursed_amount' => round((float) $disbursed, 2),
            'pending_quotas_count' => (int) ((clone $rowsAfterMilestone)
                ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
                ->count(DB::raw("DISTINCT CONCAT(q.contract_id, '|', q.quota_number)"))),
        ];
    }

    public function evolution(Carbon $start, Carbon $end, array $filters, $user = null): array
    {
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $initial = $this->snapshot($start, $filters, $user)['current_portfolio'];
        $disbursements = $this->contractsQuery($endDate, $filters, $user)
            ->whereDate('contracts.date', '>', $startDate)
            ->whereDate('contracts.date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(contracts.requested_amount), 0) as capital, COALESCE(SUM(contracts.interest), 0) as interest, COALESCE(SUM(contracts.payable_amount), 0) as total')
            ->first();

        $payments = $this->paymentsQuery($filters, $user)
            ->whereDate('payments.date', '>', $startDate)
            ->whereDate('payments.date', '<=', $endDate)
            ->sum('payments.amount');

        $deteriorated = $this->deterioratedAmount($startDate, $endDate, $filters, $user);
        $final = $initial + (float) ($disbursements->total ?? 0) - (float) $payments - $deteriorated;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'initial_balance' => round((float) $initial, 2),
            'disbursed_capital' => round((float) ($disbursements->capital ?? 0), 2),
            'generated_interest' => round((float) ($disbursements->interest ?? 0), 2),
            'increments' => round((float) ($disbursements->total ?? 0), 2),
            'payments' => round((float) $payments, 2),
            'deteriorated_over_120' => round((float) $deteriorated, 2),
            'reductions' => round((float) $payments + $deteriorated, 2),
            'final_balance' => round(max(0, $final), 2),
            'daily' => $this->dailyEvolution($start, $end, $filters, $user, (float) $initial),
        ];
    }

    private function dailyEvolution(Carbon $start, Carbon $end, array $filters, $user, float $initial): array
    {
        $days = [];
        $balance = $initial;
        $cursor = $start->copy()->addDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $previous = $cursor->copy()->subDay()->toDateString();
            $disbursed = $this->contractsQuery($date, $filters, $user)
                ->whereDate('contracts.date', $date)
                ->sum('contracts.payable_amount');
            $payments = $this->paymentsQuery($filters, $user)
                ->whereDate('payments.date', $date)
                ->sum('payments.amount');
            $deteriorated = $this->deterioratedAmount($previous, $date, $filters, $user);
            $balance = max(0, $balance + (float) $disbursed - (float) $payments - (float) $deteriorated);

            $days[] = [
                'date' => $date,
                'increments' => round((float) $disbursed, 2),
                'payments' => round((float) $payments, 2),
                'deteriorated_over_120' => round((float) $deteriorated, 2),
                'balance' => round($balance, 2),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    private function deterioratedAmount(string $startDate, string $endDate, array $filters, $user = null): float
    {
        return (float) DB::query()
            ->fromSub($this->quotaSnapshotQuery($endDate, $filters, $user), 'q')
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->whereRaw('DATE_ADD(q.quota_date, INTERVAL 121 DAY) > ?', [$startDate])
            ->whereRaw('DATE_ADD(q.quota_date, INTERVAL 121 DAY) <= ?', [$endDate])
            ->sum(DB::raw('q.amount - q.paid_to_cutoff'));
    }

    private function quotaSnapshotQuery(string $asOf, array $filters, $user, bool $afterMilestoneOnly = true)
    {
        $milestoneDate = $this->date($asOf)->toDateString();
        $paymentCutoffDate = $this->date($asOf)->toDateString();

        return DB::table('quotas')
            ->join('contracts', 'contracts.id', '=', 'quotas.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->leftJoin('payments', function ($join) use ($paymentCutoffDate) {
                $join->on('payments.quota_id', '=', 'quotas.id')
                    ->where('payments.deleted', 0)
                    ->whereRaw('DATE(payments.date) <= ?', [$paymentCutoffDate]);
            })
            ->where('contracts.deleted', 0)
            ->whereRaw('DATE(contracts.date) <= ?', [$milestoneDate])
            ->when($afterMilestoneOnly, fn($q) => $q->where('quotas.date', '>=', $milestoneDate))
            ->when($user && $user->hasRole('seller'), fn($q) => $q->where('contracts.seller_id', $user->id))
            ->when($user && $user->hasRole('credit_manager'), fn($q) => $q->where('users.credit_manager_id', $user->id))
            ->when($filters['credit_manager_id'] ?? null, fn($q, $id) => $q->where('users.credit_manager_id', $id))
            ->when($filters['seller_id'] ?? null, fn($q, $id) => $q->where('contracts.seller_id', $id))
            ->groupBy(
                'quotas.id',
                'quotas.contract_id',
                'quotas.number',
                'quotas.person_name',
                'quotas.person_document',
                'quotas.amount',
                'quotas.date',
                'contracts.client_type'
            )
            ->selectRaw('
                quotas.id as quota_id,
                quotas.contract_id,
                quotas.number as quota_number,
                quotas.person_name,
                quotas.person_document,
                contracts.client_type,
                quotas.amount,
                quotas.date as quota_date,
                COALESCE(SUM(payments.amount), 0) as paid_to_cutoff
            ');
    }

    private function quotaBalanceDetailsQuery(string $asOf, array $filters, $user, bool $afterMilestoneOnly = true)
    {
        $milestoneDate = $this->date($asOf)->toDateString();

        return DB::query()
            ->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, $afterMilestoneOnly), 'q')
            ->join('contracts', 'contracts.id', '=', 'q.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->when($afterMilestoneOnly, fn($q) => $q->whereRaw('DATE(q.quota_date) >= ?', [$milestoneDate]))
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->selectRaw("
                contracts.number_pagare,
                contracts.client_type,
                contracts.name,
                contracts.group_name,
                users.name as seller_name,
                DATE_FORMAT(contracts.date, '%d/%m/%Y') as contract_date,
                q.quota_number,
                q.person_name,
                q.person_document,
                q.amount as quota_amount,
                q.paid_to_cutoff,
                q.amount - q.paid_to_cutoff as balance,
                DATE_FORMAT(q.quota_date, '%d/%m/%Y') as quota_date,
                GREATEST(DATEDIFF(?, q.quota_date), 0) as due_days
            ", [$asOf])
            ->orderBy('q.quota_date')
            ->orderBy('contracts.number_pagare');
    }

    private function contractBalanceDetailsQuery(string $asOf, array $filters, $user)
    {
        $clientCounts = DB::query()
            ->fromSub($this->clientSnapshotQuery($asOf, $filters, $user), 'c')
            ->groupBy('c.contract_id')
            ->selectRaw("
                c.contract_id,
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 <= 0.009 THEN c.client_key END) as client_count,
                COUNT(DISTINCT CASE WHEN c.arrears_over_120 > 0.009 THEN c.client_key END) as client_count_over_120
            ");

        return DB::query()
            ->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, false), 'q')
            ->join('contracts', 'contracts.id', '=', 'q.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->leftJoinSub($clientCounts, 'client_counts', function ($join) {
                $join->on('client_counts.contract_id', '=', 'contracts.id');
            })
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->groupBy(
                'contracts.id',
                'contracts.number_pagare',
                'contracts.client_type',
                'contracts.name',
                'contracts.group_name',
                'contracts.requested_amount',
                'contracts.date',
                'users.name',
                'client_counts.client_count',
                'client_counts.client_count_over_120'
            )
            ->selectRaw("
                contracts.id as contract_id,
                contracts.number_pagare,
                contracts.client_type,
                contracts.name,
                contracts.group_name,
                contracts.requested_amount,
                DATE_FORMAT(contracts.date, '%d/%m/%Y') as date,
                users.name as seller_name,
                SUM(q.amount - q.paid_to_cutoff) as balance,
                SUM(CASE WHEN DATEDIFF(?, q.quota_date) BETWEEN 1 AND 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END) as arrears_1_120,
                SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END) as arrears_over_120,
                COUNT(DISTINCT q.quota_number) as pending_quotas_count,
                COALESCE(client_counts.client_count, 0) as client_count,
                COALESCE(client_counts.client_count_over_120, 0) as client_count_over_120
            ", [$asOf, $asOf])
            ->orderByDesc('contracts.date');
    }

    private function clientSnapshotQuery(string $asOf, array $filters, $user)
    {
        return DB::query()
            ->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user, false), 'q')
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->groupBy(
                'q.contract_id',
                'q.client_type',
                DB::raw("
                    CASE
                        WHEN q.client_type = 'Personal' THEN CONCAT('P|', q.contract_id)
                        ELSE CONCAT('G|', q.contract_id, '|', COALESCE(NULLIF(TRIM(q.person_document), ''), NULLIF(TRIM(q.person_name), ''), 'SIN_PERSONA'))
                    END
                ")
            )
            ->selectRaw("
                q.contract_id,
                q.client_type,
                CASE
                    WHEN q.client_type = 'Personal' THEN CONCAT('P|', q.contract_id)
                    ELSE CONCAT('G|', q.contract_id, '|', COALESCE(NULLIF(TRIM(q.person_document), ''), NULLIF(TRIM(q.person_name), ''), 'SIN_PERSONA'))
                END as client_key,
                SUM(q.amount - q.paid_to_cutoff) as balance,
                SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END) as arrears_over_120
            ", [$asOf]);
    }

    private function contractsQuery(string $asOf, array $filters, $user)
    {
        return DB::table('contracts')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->where('contracts.deleted', 0)
            ->whereDate('contracts.date', '<=', $asOf)
            ->when($user && $user->hasRole('seller'), fn($q) => $q->where('contracts.seller_id', $user->id))
            ->when($user && $user->hasRole('credit_manager'), fn($q) => $q->where('users.credit_manager_id', $user->id))
            ->when($filters['credit_manager_id'] ?? null, fn($q, $id) => $q->where('users.credit_manager_id', $id))
            ->when($filters['seller_id'] ?? null, fn($q, $id) => $q->where('contracts.seller_id', $id));
    }

    private function paymentsQuery(array $filters, $user)
    {
        return DB::table('payments')
            ->join('quotas', 'quotas.id', '=', 'payments.quota_id')
            ->join('contracts', 'contracts.id', '=', 'quotas.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->where('payments.deleted', 0)
            ->where('contracts.deleted', 0)
            ->when($user && $user->hasRole('seller'), fn($q) => $q->where('contracts.seller_id', $user->id))
            ->when($user && $user->hasRole('credit_manager'), fn($q) => $q->where('users.credit_manager_id', $user->id))
            ->when($filters['credit_manager_id'] ?? null, fn($q, $id) => $q->where('users.credit_manager_id', $id))
            ->when($filters['seller_id'] ?? null, fn($q, $id) => $q->where('contracts.seller_id', $id));
    }

    private function date($date): Carbon
    {
        return Carbon::parse($date)->startOfDay();
    }
}
