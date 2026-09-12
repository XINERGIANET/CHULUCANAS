<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('document', 20)->nullable();
                $table->string('name', 255);
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->text('address')->nullable();
                $table->string('position', 100)->nullable(); // Asesor comercial, Operaciones, etc.
                $table->date('entry_date'); // Fecha de ingreso a laborar
                $table->string('regime', 50)->default('REMYPE'); // REMYPE (15 días)
                $table->integer('annual_vacation_days')->default(15);
                $table->tinyInteger('status')->default(1); // 1: Activo, 0: Inactivo/Cesado
                $table->date('termination_date')->nullable(); // Fecha de cese si aplica
                $table->tinyInteger('deleted')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('employee_vacations')) {
            Schema::create('employee_vacations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('days', 8, 2); // Días tomados (ej: 7, 15, etc.)
                $table->string('period_year', 50)->nullable();
                $table->text('comments')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->tinyInteger('deleted')->default(0);
                $table->timestamps();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_vacations');
        Schema::dropIfExists('employees');
    }
};
