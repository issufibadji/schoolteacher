<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Models\Audit;

/**
 * "Acessar como": o admin assume a sessão de outro usuário (suporte/manutenção)
 * e depois volta pra própria conta. Só admin, nunca outro admin nem ele mesmo.
 *
 * Troca o usuário da sessão direto no guard, sem Auth::login(), pra não gerar
 * um evento de login falso do alvo na auditoria — o início e o fim do acesso
 * ficam registrados como eventos próprios (impersonate-start/stop).
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonador_id';

    public function podeAcessarComo(User $admin, User $alvo): bool
    {
        return $admin->hasRole('admin')
            && ! $this->ativo()
            && $admin->id !== $alvo->id
            && ! $alvo->hasRole('admin');
    }

    public function iniciar(User $admin, User $alvo): void
    {
        abort_unless($this->podeAcessarComo($admin, $alvo), 403);

        $this->auditar('impersonate-start', $admin, $alvo);

        $this->trocarUsuarioDaSessao($alvo);

        session([
            self::SESSION_KEY => $admin->id,
            // O 2FA de quem está acessando é o do admin, que já passou por ele.
            '2fa_passed' => true,
        ]);
    }

    public function encerrar(): User
    {
        $admin = $this->impersonador();
        $alvo = Auth::user();

        abort_unless($admin, 403);

        $this->auditar('impersonate-stop', $admin, $alvo);

        $this->trocarUsuarioDaSessao($admin);

        session()->forget(self::SESSION_KEY);

        return $admin;
    }

    public function ativo(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function impersonador(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    private function trocarUsuarioDaSessao(User $user): void
    {
        $guard = Auth::guard('web');

        // Novo id de sessão a cada troca de identidade (evita session fixation).
        session()->migrate(true);
        session()->put($guard->getName(), $user->getAuthIdentifier());
        // Perfil ativo (diretor/professor) é de quem estava logado, não do novo usuário.
        session()->forget('perfil_ativo');

        $guard->setUser($user);
    }

    private function auditar(string $evento, User $admin, ?User $alvo): void
    {
        Audit::create([
            'user_type' => User::class,
            'user_id' => $admin->id,
            'event' => $evento,
            'auditable_type' => User::class,
            'auditable_id' => $alvo?->id,
            'old_values' => [],
            'new_values' => ['alvo' => $alvo?->email],
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => 'auth',
        ]);
    }
}
