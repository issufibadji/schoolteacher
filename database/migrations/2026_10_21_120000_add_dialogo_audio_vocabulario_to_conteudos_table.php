<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo "dialogo" (falas "Personagem: frase | tradução" no corpo), áudio
     * opcional do conteúdo e lista de vocabulário ("palavra | tradução").
     */
    public function up(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->enum('tipo', ['video', 'video_curto', 'pdf', 'texto', 'dialogo', 'exercicio', 'link'])->change();
            $table->string('audio_path')->nullable()->after('arquivo_path');
            $table->text('vocabulario')->nullable()->after('corpo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conteudos', function (Blueprint $table) {
            $table->dropColumn(['audio_path', 'vocabulario']);
        });

        Schema::table('conteudos', function (Blueprint $table) {
            $table->enum('tipo', ['video', 'video_curto', 'pdf', 'texto', 'exercicio', 'link'])->change();
        });
    }
};
