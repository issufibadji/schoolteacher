<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = ['professor_id', 'nome', 'tipo', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    /**
     * Dono do curso (quem criou). Null = curso do admin.
     */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    /**
     * Escopo: cursos que $user enxerga. Admin vê todos; professor só os dele.
     */
    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin') ? $query : $query->where('professor_id', $user->id);
    }
}
