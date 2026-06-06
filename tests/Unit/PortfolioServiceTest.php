<?php

namespace Tests\Unit;

use App\Services\PortfolioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        DB::purge('sqlite');

        $pdo = DB::connection('sqlite')->getPdo();
        $pdo->sqliteCreateFunction('DATEDIFF', function ($date1, $date2) {
            return (int) floor((strtotime($date1) - strtotime($date2)) / 86400);
        });
        $pdo->sqliteCreateFunction('CONCAT', function (...$values) {
            return implode('', $values);
        });

        DB::connection('sqlite')->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, credit_manager_id INTEGER NULL, role TEXT NULL)');
        DB::connection('sqlite')->statement('CREATE TABLE contracts (id INTEGER PRIMARY KEY AUTOINCREMENT, deleted INTEGER DEFAULT 0, date TEXT NOT NULL, seller_id INTEGER NULL, client_type TEXT NULL, number_pagare TEXT NULL, name TEXT NULL, group_name TEXT NULL, requested_amount REAL DEFAULT 0, interest REAL DEFAULT 0, payable_amount REAL DEFAULT 0, paid INTEGER DEFAULT 0)');
        DB::connection('sqlite')->statement('CREATE TABLE quotas (id INTEGER PRIMARY KEY AUTOINCREMENT, contract_id INTEGER NOT NULL, number INTEGER NOT NULL, person_name TEXT NULL, person_document TEXT NULL, amount REAL DEFAULT 0, debt REAL DEFAULT 0, date TEXT NOT NULL, paid INTEGER DEFAULT 0)');
        DB::connection('sqlite')->statement('CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, quota_id INTEGER NOT NULL, amount REAL DEFAULT 0, date TEXT NOT NULL, due_days INTEGER DEFAULT 0, deleted INTEGER DEFAULT 0)');

        DB::connection('sqlite')->table('users')->insert([
            'id' => 1,
            'name' => 'Seller One',
            'credit_manager_id' => 10,
            'role' => 'seller',
        ]);

        DB::connection('sqlite')->table('contracts')->insert([
            'id' => 1,
            'deleted' => 0,
            'date' => '2026-05-01',
            'seller_id' => 1,
            'client_type' => 'Personal',
            'number_pagare' => 'P-001',
            'name' => 'Cliente Uno',
            'group_name' => null,
            'requested_amount' => 300,
            'interest' => 0,
            'payable_amount' => 300,
            'paid' => 0,
        ]);

        DB::connection('sqlite')->table('quotas')->insert([
            ['id' => 1, 'contract_id' => 1, 'number' => 1, 'person_name' => 'Cliente Uno', 'person_document' => '11111111', 'amount' => 100, 'debt' => 100, 'date' => '2026-05-01', 'paid' => 0],
            ['id' => 2, 'contract_id' => 1, 'number' => 2, 'person_name' => 'Cliente Uno', 'person_document' => '11111111', 'amount' => 200, 'debt' => 200, 'date' => '2026-06-15', 'paid' => 0],
        ]);
    }

    public function test_snapshot_ignores_future_quotas_when_cutoff_is_before_their_due_date(): void
    {
        $service = new PortfolioService();

        $snapshot = $service->snapshot(Carbon::parse('2026-06-01'), [], null);

        $this->assertSame(100.0, $snapshot['gross_portfolio']);
        $this->assertSame(100.0, $snapshot['current_portfolio']);
        $this->assertSame(100.0, $snapshot['arrears_1_120']);
        $this->assertSame(1, $snapshot['pending_quotas_count']);
    }
}
