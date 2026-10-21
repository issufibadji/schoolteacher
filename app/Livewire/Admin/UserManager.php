<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use App\Services\ImpersonationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.master')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $verifiedFilter = '';

    public string $roleFilter = '';

    public ?int $userId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public array $roles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVerifiedFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', User::class);

        $this->reset(['userId', 'name', 'email', 'password', 'roles']);

        if (! $this->ehAdmin()) {
            $this->roles = ['aluno'];
        }

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'user-form');
    }

    /**
     * Admin escolhe qualquer papel; professor só cadastra/edita aluno.
     */
    private function ehAdmin(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public function edit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        Gate::authorize('update', $user);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->roles = $user->roles->pluck('name')->all();

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'user-form');
    }

    public function save(): void
    {
        $isNew = ! $this->userId;

        $user = $isNew ? new User : User::findOrFail($this->userId);

        Gate::authorize($isNew ? 'create' : 'update', $isNew ? User::class : $user);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => $this->userId ? 'nullable|string|min:8' : 'required|string|min:8',
        ]);

        // Professor só cadastra aluno: o papel é forçado no servidor, não importa o
        // que veio do formulário. Admin escolhe entre os papéis que existem.
        if ($this->ehAdmin()) {
            $this->validate([
                'roles' => ['array'],
                'roles.*' => Rule::exists('roles', 'name'),
            ]);
        } else {
            $this->roles = ['aluno'];
        }

        $user->name = $this->name;
        $user->email = $this->email;

        if ($this->password) {
            $user->password = Hash::make($this->password);
        }

        if ($isNew) {
            $user->email_verified_at = now();
            // Quem cadastrou: é assim que o professor continua vendo o aluno antes de matricular.
            $user->criado_por = Auth::id();
        }

        $user->save();
        $user->syncRoles($this->roles);

        if ($isNew) {
            $user->notify(new UserAccountCreated);
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Usuário salvo com sucesso.');
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('update', $user);

        $user->forceFill(['active' => ! $user->active])->save();
    }

    public function toggleRequires2fa(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('update', $user);
        // Exigir 2FA é configuração de segurança: só o admin.
        abort_unless($this->ehAdmin(), 403);

        $user->forceFill(['requires_2fa' => ! $user->requires_2fa])->save();
    }

    public function acessarComo(int $id, ImpersonationService $impersonation): void
    {
        $impersonation->iniciar(Auth::user(), User::findOrFail($id));

        $this->redirectRoute('dashboard', navigate: false);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('delete', $user);

        $user->delete();

        session()->flash('success', 'Usuário removido.');
    }

    public function render()
    {
        $ator = Auth::user();

        $users = User::with('roles')
            ->gerenciavelPor($ator)
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('active', $this->statusFilter === 'active'))
            ->when($this->verifiedFilter !== '', fn ($query) => $this->verifiedFilter === 'verified'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at'))
            ->when($this->roleFilter, fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter)))
            ->orderBy('name')
            ->paginate(15);

        $allRoles = $ator->hasRole('admin')
            ? Role::orderBy('name')->get()
            : Role::where('name', 'aluno')->get();

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'allRoles' => $allRoles,
            'ehAdmin' => $ator->hasRole('admin'),
            'impersonation' => app(ImpersonationService::class),
        ]);
    }
}
