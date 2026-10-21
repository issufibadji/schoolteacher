<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Ficam só admin, professor e aluno:
     * - quem era manager passa a ser professor, e o professor herda as atribuições
     *   do manager (cursos, turmas e matrículas) — sempre restritas ao que é dele;
     * - os papéis manager e operator deixam de existir;
     * - cursos já existentes ganham dono quando só um professor tem turma neles
     *   (os demais ficam com o admin, que pode atribuir depois);
     * - sai o item de menu "Minhas Turmas" (o professor usa "Turmas", já filtrada).
     *
     * Feito em migration (e não só em seeder) pra valer sozinho no deploy.
     */
    private const PERMISSOES_PROFESSOR = [
        'manage-cursos', 'manage-turmas', 'manage-matriculas', 'manage-own-turmas',
        // Cadastro de aluno na tela "Usuários" (UserPolicy limita a alunos dele).
        'view-users', 'create-users', 'edit-users', 'delete-users',
    ];

    public function up(): void
    {
        $agora = now();
        $guard = 'web';

        $professorId = $this->idPapel('professor', $guard, $agora);

        foreach (self::PERMISSOES_PROFESSOR as $nome) {
            $permissaoId = DB::table('permissions')->where(['name' => $nome, 'guard_name' => $guard])->value('id')
                ?? DB::table('permissions')->insertGetId(['name' => $nome, 'guard_name' => $guard, 'created_at' => $agora, 'updated_at' => $agora]);

            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissaoId, 'role_id' => $professorId]);
        }

        $papeisRemovidos = DB::table('roles')->whereIn('name', ['manager', 'operator'])->where('guard_name', $guard)->pluck('id', 'name');

        if ($managerId = $papeisRemovidos->get('manager')) {
            DB::table('model_has_roles')->where('role_id', $managerId)->get()->each(
                fn ($vinculo) => DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $professorId,
                    'model_type' => $vinculo->model_type,
                    'model_id' => $vinculo->model_id,
                ]),
            );
        }

        if ($papeisRemovidos->isNotEmpty()) {
            DB::table('model_has_roles')->whereIn('role_id', $papeisRemovidos)->delete();
            DB::table('role_has_permissions')->whereIn('role_id', $papeisRemovidos)->delete();
            DB::table('roles')->whereIn('id', $papeisRemovidos)->delete();
        }

        // Dono dos cursos existentes: o único professor que tem turma neles.
        DB::table('turmas')
            ->select('curso_id')
            ->selectRaw('min(professor_id) as professor_id')
            ->groupBy('curso_id')
            ->havingRaw('count(distinct professor_id) = 1')
            ->get()
            ->each(fn ($linha) => DB::table('cursos')
                ->where('id', $linha->curso_id)
                ->whereNull('professor_id')
                ->update(['professor_id' => $linha->professor_id]));

        DB::table('menu_side_bars')->where('route_name', 'academico.minhas-turmas.index')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Recria os papéis vazios; quem era manager continua professor (não dá pra
     * distinguir de volta) e os donos de curso atribuídos são mantidos.
     */
    public function down(): void
    {
        $agora = now();

        foreach (['manager', 'operator'] as $nome) {
            $this->idPapel($nome, 'web', $agora);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function idPapel(string $nome, string $guard, $agora): int
    {
        return DB::table('roles')->where(['name' => $nome, 'guard_name' => $guard])->value('id')
            ?? DB::table('roles')->insertGetId(['name' => $nome, 'guard_name' => $guard, 'created_at' => $agora, 'updated_at' => $agora]);
    }
};
