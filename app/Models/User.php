<?php

namespace App\Models;

use App\Concerns\HasPushSubscriptions;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements AuditableContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasPushSubscriptions, HasRoles, Notifiable;

    public const ROTULOS_PERFIL = [
        'admin' => 'Administrador',
        'professor' => 'Professor',
        'aluno' => 'Aluno',
    ];

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
     * Quem cadastrou essa conta (ex.: o professor que criou o aluno).
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    /**
     * Papel mostrado na tela ("Sua função").
     */
    public function perfilExibido(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public static function rotuloPerfil(?string $perfil): string
    {
        return $perfil ? (self::ROTULOS_PERFIL[$perfil] ?? ucfirst($perfil)) : 'Sem função';
    }

    /**
     * Escopo: alunos de um professor — matriculados em alguma turma dele ou
     * cadastrados por ele (ainda sem matrícula). Nunca os de outro professor.
     */
    public function scopeAlunosDoProfessor(Builder $query, User $professor): Builder
    {
        return $query->role('aluno')->where(fn (Builder $q) => $q
            ->where('criado_por', $professor->id)
            ->orWhereHas('turmasMatriculadas', fn (Builder $t) => $t->where('professor_id', $professor->id)));
    }

    /**
     * Escopo: usuários que $ator vê na tela "Usuários". Admin vê todo mundo;
     * professor só os alunos dele; qualquer outro papel não vê ninguém.
     */
    public function scopeGerenciavelPor(Builder $query, User $ator): Builder
    {
        if ($ator->hasRole('admin')) {
            return $query;
        }

        return $ator->hasRole('professor') ? $query->alunosDoProfessor($ator) : $query->whereRaw('1 = 0');
    }
}
