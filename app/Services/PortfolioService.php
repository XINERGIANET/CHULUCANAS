<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function dashboard(Request $request, $user): array
    {
        $cutoff = $this->date($request->end_date_2 ?: now());
        $start = $request->start_date_2 ? $this->date($request->start_date_2) : null;

        $filters = [
            'credit_manager_id' => $request->credit_manager_id,
            'seller_id' => $request->seller_id_2,
        ];

        $snapshot = $this->snapshot($cutoff, $filters, $user);
        $evolution = $start ? $this->evolution($start, $cutoff, $filters, $user) : null;

        return [
            'cutoff' => $snapshot,
            'evolution' => $evolution,
        ];
    }

    public function snapshot(Carbon $cutoff, array $filters, $user = null): array
    {
        $asOf = $cutoff->toDateString();
        $rows = DB::query()->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user), 'q');

        $totals = (clone $rows)
            ->whereRaw('(q.amount - q.paid_to_cutoff) > 0.009')
            ->selectRaw("
                COALESCE(SUM(q.amount - q.paid_to_cutoff), 0) as gross_portfolio,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) BETWEEN 1 AND 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as arrears_1_120,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as arrears_over_120,
                COALESCE(SUM(CASE WHEN DATEDIFF(?, q.quota_date) <= 0 THEN q.amount - q.paid_to_cutoff ELSE 0 END), 0) as current_installments,
                COUNT(*) as pending_quotas_count,
                COUNT(DISTINCT q.contract_id) as active_clients,
                COUNT(DISTINCT CASE WHEN DATEDIFF(?, q.quota_date) > 120 THEN q.contract_id END) as clients_over_120,
                COUNT(DISTINCT CASE WHEN q.client_type = 'Personal' THEN q.contract_id END) as individual_clients,
                COUNT(DISTINCT CASE WHEN q.client_type = 'Grupo' THEN q.contract_id END) as group_clients
            ", [$asOf, $asOf, $asOf, $asOf])
            ->first();

        $disbursed = $this->contractsQuery($asOf, $filters, $user)
            ->sum('contracts.requested_amount');

        $finishedWithArrears = DB::query()
            ->fromSub($this->quotaSnapshotQuery($asOf, $filters, $user), 'q')
            ->join('payments', 'payments.quota_id', '=', 'q.quota_id')
            ->where('payments.deleted', 0)
            ->whereDate('payments.date', '<=', $asOf)
            ->whereBetween('payments.due_days', [1, 120])
            ->select('q.contract_id')
            ->groupBy('q.contract_id')
            ->havingRaw('SUM(q.amount - q.paid_to_cutoff) <= 0.009')
            ->get()
            ->count();

        $gross = (float) ($totals->gross_portfolio ?? 0);
        $arrears1To120 = (float) ($totals->arrears_1_120 ?? 0);
        $arrearsOver120 = (float) ($totals->arrears_over_120 ?? 0);
        $currentPortfolio = max(0, $gross - $arrearsOver120);

        return [
            'gross_portfolio' => round($gross, 2),
            'current_portfolio' => round($currentPortfolio, 2),
            'current_installments' => round((float) ($totals->current_installments ?? 0), 2),
            'arrears_1_120' => round($arrears1To120, 2),
            'arrears_over_120' => round($arrearsOver120, 2),
            'arrears_total' => round($arrears1To120 + $arrearsOver120, 2),
            'arrears_percent' => $currentPortfolio > 0 ? round(($arrears1To120 / $currentPortfolio) * 100, 2) : 0,
            'active_clients' => (int) ($totals->active_clients ?? 0),
            'clients_over_120' => (int) ($totals->clients_over_120 ?? 0),
            'individual_clients' => (int) ($totals->individual_clients ?? 0),
            'group_clients' => (int) ($totals->group_clients ?? 0),
            'finished_clients_with_arrears_1_120' => $finishedWithArrears,
            'disbursed_amount' => round((float) $disbursed, 2),
            'pending_quotas_count' => (int) ($totals->pending_quotas_count ?? 0),
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

    private function quotaSnapshotQuery(string $asOf, array $filters, $user)
    {
        return DB::table('quotas')
            ->join('contracts', 'contracts.id', '=', 'quotas.contract_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.seller_id')
            ->leftJoin('payments', function ($join) use ($asOf) {
                $join->on('payments.quota_id', '=', 'quotas.id')
                    ->where('payments.deleted', 0)
                    ->whereDate('payments.date', '<=', $asOf);
            })
            ->where('contracts.deleted', 0)
            ->whereDate('contracts.date', '<=', $asOf)
            ->when($user && $user->hasRole('seller'), fn($q) => $q->where('contracts.seller_id', $user->id))
            ->when($user && $user->hasRole('credit_manager'), fn($q) => $q->where('users.credit_manager_id', $user->id))
            ->when($filters['credit_manager_id'] ?? null, fn($q, $id) => $q->where('users.credit_manager_id', $id))
            ->when($filters['seller_id'] ?? null, fn($q, $id) => $q->where('contracts.seller_id', $id))
            ->groupBy(
                'quotas.id',
                'quotas.contract_id',
                'quotas.amount',
                'quotas.date',
                'contracts.client_type'
            )
            ->selectRaw('
                quotas.id as quota_id,
                quotas.contract_id,
                contracts.client_type,
                quotas.amount,
                quotas.date as quota_date,
                COALESCE(SUM(payments.amount), 0) as paid_to_cutoff
            ');
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
