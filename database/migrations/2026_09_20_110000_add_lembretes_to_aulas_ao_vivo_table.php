<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aulas_ao_vivo', function (Blueprint $table) {
            $table->dateTime('lembrete_previo_em')->nullable()->after('encerrada_em');
            $table->dateTime('lembrete_inicio_em')->nullable()->after('lembrete_previo_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aulas_ao_vivo', function (Blueprint $table) {
            $table->dropColumn(['lembrete_previo_em', 'lembrete_inicio_em']);
        });
    }
};
