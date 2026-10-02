<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kode+Mono:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface text-text-primary antialiased">
    @php
        // Rótulo da aba recortada no painel decorativo, conforme a tela de auth atual.
        $aba = match (true) {
            request()->routeIs('password.request') => 'Recuperar',
            request()->routeIs('password.reset') => 'Nova senha',
            request()->routeIs('two-factor.challenge') => 'Verificação',
            default => 'Entrar',
        };
    @endphp

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="glow-border w-full max-w-4xl flex bg-surface-card border border-surface-border rounded-3xl overflow-hidden">

            {{-- Painel decorativo: faixas diagonais em "<" + aba recortada --}}
            <div class="hidden lg:block relative w-5/12 shrink-0 bg-linear-to-br from-primary-dark via-primary to-accent">
                <div class="absolute inset-0 overflow-hidden">
                    {{-- faixa de cima (desce da direita pra ponta do "<") --}}
                    <div class="absolute -left-24 top-[46%] h-28 w-[160%] origin-left -rotate-45 bg-white/15 shadow-2xl"></div>
                    <div class="absolute -left-24 top-[46%] h-14 w-[160%] origin-left -rotate-45 bg-white/10"></div>
                    {{-- faixa de baixo (sobe da ponta do "<" pra direita) --}}
                    <div class="absolute -left-24 top-[54%] h-28 w-[160%] origin-left rotate-45 bg-black/15 shadow-2xl"></div>
                    <div class="absolute -left-24 top-[54%] h-14 w-[160%] origin-left rotate-45 bg-white/10"></div>
                    {{-- véu claro à direita, como na referência --}}
                    <div class="absolute inset-y-0 right-0 w-1/2 bg-linear-to-l from-white/25 to-transparent"></div>
                </div>

                <div class="relative h-full flex flex-col justify-between p-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center shadow-lg">
                            <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-lg font-semibold text-white">{{ config('app.name') }}</span>
                    </div>

                    <div>
                        <p class="text-white/70 text-xs uppercase tracking-widest">Bem-vindo</p>
                        <h2 class="text-white text-2xl font-semibold mt-2 leading-tight">
                            Gestão inteligente,<br>em um só lugar.
                        </h2>
                    </div>
                </div>

                {{-- Aba recortada: mesma cor do card, "encaixada" no painel com cantos côncavos --}}
                <div class="absolute right-0 top-1/2 -translate-y-1/2 flex flex-col items-end">
                    <span class="block w-6 h-6 bg-[radial-gradient(circle_at_top_left,transparent_23px,var(--color-surface-card)_24px)]"></span>
                    <span class="pl-8 pr-10 py-4 rounded-l-full bg-surface-card text-sm font-bold uppercase tracking-wider text-text-primary">
                        {{ $aba }}
                    </span>
                    <span class="block w-6 h-6 bg-[radial-gradient(circle_at_bottom_left,transparent_23px,var(--color-surface-card)_24px)]"></span>
                </div>
            </div>

            {{-- Lado do formulário --}}
            <div class="flex-1 flex flex-col min-w-0">
                <div class="flex-1 px-8 sm:px-12 py-10 flex flex-col justify-center">
                    {{-- Marca no mobile (o painel decorativo some abaixo de lg) --}}
                    <div class="lg:hidden flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30">
                            <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
                        </div>
                        <span class="text-lg font-semibold text-text-primary">{{ config('app.name') }}</span>
                    </div>

                    <x-flash-messages />

                    {{ $slot }}
                </div>

                <div class="px-8 sm:px-12 py-4 border-t border-surface-border shadow-[0_-8px_20px_-16px_rgb(0_0_0/0.35)]">
                    <p class="text-xs text-text-secondary">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
