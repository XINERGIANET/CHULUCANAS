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
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('codigo_grupo', 20)->unique();
                $table->string('name', 191);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('contracts') && !Schema::hasColumn('contracts', 'group_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->foreignId('group_id')->nullable()->after('client_type')->constrained('groups')->nullOnDelete();
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
        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'group_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            });
        }

        Schema::dropIfExists('groups');
    }
};
