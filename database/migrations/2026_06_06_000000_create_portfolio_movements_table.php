<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfolioMovementsTable extends Migration
{
    public function up()
    {
        Schema::create('portfolio_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->index();
            $table->foreignId('quota_id')->nullable()->index();
            $table->foreignId('payment_id')->nullable()->index();
            $table->date('movement_date')->index();
            $table->enum('type', [
                'disbursement_capital',
                'disbursement_interest',
                'payment',
                'arrears_deterioration_120',
                'payment_reversal',
            ])->index();
            $table->decimal('amount', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['movement_date', 'type']);
            $table->index(['contract_id', 'movement_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('portfolio_movements');
    }
}
