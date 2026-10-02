<?php

namespace App\Models;

use App\Concerns\HasPushSubscriptions;
use App\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements AuditableContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasPushSubscriptions, Notifiable;
    use HasRoles {
        hasPermissionViaRole as protected hasPermissionViaRoleSpatie;
    }

    /**
     * Perfis entre os quais quem acumula os dois alterna (ex.: diretor que
     * também dá aula). Só um fica ativo por vez; a ordem define o padrão.
     */
    public const PERFIS_ALTERNAVEIS = ['manager', 'professor'];

    public const ROTULOS_PERFIL = [
        'admin' => 'Administrador',
        'manager' => 'Diretor',
        'professor' => 'Professor',
        'aluno' => 'Aluno',
        'operator' => 'Operador',
    ];

    private const SESSAO_PERFIL = 'perfil_ativo';

    /**
     * Attributes excluded from the audit trail (sensitive/security data).
     *
     * @var array<int, string>
     */
    protected $auditExclude = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'active' => 'boolean',
            'requires_2fa' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function additionalData(): HasMany
    {
        return $this->hasMany(UserAdditionalData::class);
    }

    public function turmasComoProfessor(): HasMany
    {
        return $this->hasMany(Turma::class, 'professor_id');
    }

    public function turmasMatriculadas(): BelongsToMany
    {
        return $this->belongsToMany(Turma::class, 'turma_aluno', 'aluno_id', 'turma_id')
            ->withPivot(['data_matricula', 'status'])
            ->withTimestamps();
    }

    /**
     * Perfis alternáveis que esse usuário tem, na ordem de PERFIS_ALTERNAVEIS.
     *
     * @return array<int, string>
     */
    public function perfisAlternaveis(): array
    {
        $roles = $this->getRoleNames();

        return array_values(array_filter(self::PERFIS_ALTERNAVEIS, fn (string $perfil) => $roles->contains($perfil)));
    }

    public function podeTrocarPerfil(): bool
    {
        return count($this->perfisAlternaveis()) > 1;
    }

    /**
     * Perfil em que o usuário está atuando agora, ou null se ele não acumula
     * perfis alternáveis. Só vale pro usuário logado na sessão atual — checar
     * permissão de outro usuário (policy, notificação) considera todos os papéis dele.
     */
    public function perfilAtivo(): ?string
    {
        if (! $this->podeTrocarPerfil() || Auth::id() !== $this->id) {
            return null;
        }

        $escolhido = session(self::SESSAO_PERFIL);

        return in_array($escolhido, $this->perfisAlternaveis(), true) ? $escolhido : $this->perfisAlternaveis()[0];
    }

    public function trocarPerfil(string $perfil): void
    {
        abort_unless(in_array($perfil, $this->perfisAlternaveis(), true), 403);

        session([self::SESSAO_PERFIL => $perfil]);
    }

    /**
     * Se o usuário tem o papel E, quando acumula perfis, está atuando nele agora.
     * Use no lugar de hasRole() pra decidir telas/escopo de manager e professor.
     */
    public function atuaComo(string $perfil): bool
    {
        if (! $this->hasRole($perfil)) {
            return false;
        }

        $ativo = $this->perfilAtivo();

        return $ativo === null || ! in_array($perfil, self::PERFIS_ALTERNAVEIS, true) || $ativo === $perfil;
    }

    /**
     * Papel mostrado na tela ("Sua função"): o perfil ativo, ou o primeiro papel.
     */
    public function perfilExibido(): ?string
    {
        return $this->perfilAtivo() ?? $this->getRoleNames()->first();
    }

    public static function rotuloPerfil(?string $perfil): string
    {
        return $perfil ? (self::ROTULOS_PERFIL[$perfil] ?? ucfirst($perfil)) : 'Sem função';
    }

    /**
     * Permissão via papel ignora o perfil alternável que não está ativo: quem
     * está como professor não herda as permissões de manager, e vice-versa.
     * Direct permissions e os demais papéis continuam valendo normalmente.
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        $ativo = $this->perfilAtivo();

        if ($ativo === null) {
            return $this->hasPermissionViaRoleSpatie($permission);
        }

        $inativos = array_diff(self::PERFIS_ALTERNAVEIS, [$ativo]);

        return $this->hasRole($permission->roles->reject(fn ($role) => in_array($role->name, $inativos, true)));
    }

    /**
     * Escopo: usuários que $ator pode gerenciar. Admin vê todo mundo; um
     * manager só vê usuários com papel abaixo dele (professor/aluno/operator
     * — nunca admin/outro manager). Ver UserPolicy::PAPEIS_GERENCIAVEIS_POR_MANAGER.
     */
    public function scopeGerenciavelPor(Builder $query, User $ator): Builder
    {
        if ($ator->hasRole('admin')) {
            return $query;
        }

        return $query->whereDoesntHave(
            'roles',
            fn ($q) => $q->whereNotIn('name', UserPolicy::PAPEIS_GERENCIAVEIS_POR_MANAGER),
        );
    }
}
