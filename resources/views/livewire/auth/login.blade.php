<div>
    <div class="flex flex-col items-center mb-8">
        <div class="w-20 h-20 rounded-full bg-linear-to-br from-accent to-primary-dark flex items-center justify-center shadow-xl shadow-primary/40 ring-4 ring-primary/15">
            <x-heroicon-o-user class="w-10 h-10 text-white" />
        </div>
        <h1 class="mt-4 text-2xl font-bold uppercase tracking-wide text-primary">Entrar</h1>
    </div>

    <form wire:submit="login" class="space-y-6">
        <div>
            <label for="email" class="sr-only">E-mail</label>
            <div class="flex items-center gap-3 border-b-2 border-surface-border focus-within:border-primary transition">
                <x-heroicon-s-user-circle class="w-6 h-6 shrink-0 text-text-secondary" />
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    autofocus
                    autocomplete="username"
                    placeholder="E-mail"
                    class="w-full bg-transparent border-0 px-0 py-3 text-text-primary placeholder:text-text-secondary focus:ring-0"
                >
            </div>
            @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="sr-only">Senha</label>
            <div class="flex items-center gap-3 border-b-2 border-surface-border focus-within:border-primary transition">
                <x-heroicon-s-lock-closed class="w-6 h-6 shrink-0 text-text-secondary" />
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    placeholder="Senha"
                    class="w-full bg-transparent border-0 px-0 py-3 text-text-primary placeholder:text-text-secondary focus:ring-0"
                >
            </div>
            @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" wire:model="remember" class="rounded bg-surface border-surface-border text-primary focus:ring-primary">
            Lembrar-me
        </label>

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('password.request') }}" class="text-xs font-medium text-accent hover:underline">
                Esqueceu a senha?
            </a>

            <x-button type="submit" wire:loading.attr="disabled" class="px-8 py-2.5 uppercase tracking-wider font-bold">
                Entrar
            </x-button>
        </div>
    </form>
</div>
