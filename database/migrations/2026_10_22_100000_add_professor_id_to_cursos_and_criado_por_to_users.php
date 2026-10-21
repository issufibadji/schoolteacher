<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cursos.professor_id: dono do curso (quem criou). Professor só vê/edita os dele;
     * null = curso do admin. users.criado_por: quem cadastrou a conta — o professor
     * enxerga os alunos que ele criou mesmo antes de matricular.
     */
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('professor_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('criado_por')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('professor_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('criado_por');
        });
    }
};
