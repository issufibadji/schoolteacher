<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <x-app-favicon />

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light');
        }

        document.addEventListener('alpine:init', () => {
            const telaMedia = window.matchMedia('(min-width: 768px)');

            // Estado do menu lateral, compartilhado entre a sidebar e o botão do topo.
            // < 768px: gaveta sobreposta (aberto/fechado), sem recolher.
            // >= 768px: no fluxo, recolhido/expandido salvo pelo usuário; sem
            // preferência salva, começa recolhido entre 768 e 1023px.
            Alpine.store('menu', {
                aberto: false,
                largo: telaMedia.matches,
                recolhido: false,

                init() {
                    const salvo = localStorage.getItem('sidebar-collapsed');
                    this.recolhido = salvo !== null ? salvo === 'true' : window.innerWidth < 1024;

                    telaMedia.addEventListener('change', (e) => {
                        this.largo = e.matches;
                        if (e.matches) this.aberto = false;
                    });
                },

                get modoGaveta() { return ! this.largo; },
                get compacto() { return this.largo && this.recolhido; },

                abrir() { this.aberto = true; },
                fechar() { this.aberto = false; },

                alternarRecolhido() {
                    this.recolhido = ! this.recolhido;
                    localStorage.setItem('sidebar-collapsed', this.recolhido);
                },

                expandir() {
                    this.recolhido = false;
                    localStorage.setItem('sidebar-collapsed', false);
                },
            });

            // Tabela que vira cards empilhados < 640px: copia o texto de cada <th>
            // pro data-label da célula. Reaplica quando o Livewire re-renderiza
            // (o morph remove atributos que não vêm do servidor).
            Alpine.data('tabelaResponsiva', () => ({
                init() {
                    const rotular = () => {
                        const titulos = [...this.$el.querySelectorAll('thead th')].map((th) => th.textContent.trim());
                        this.$el.querySelectorAll('tbody tr').forEach((tr) => {
                            [...tr.children].forEach((td, i) => {
                                if (td.hasAttribute('colspan')) return;
                                const rotulo = titulos[i] ?? '';
                                if (td.getAttribute('data-label') !== rotulo) td.setAttribute('data-label', rotulo);
                            });
                        });
                    };

                    rotular();
                    new MutationObserver(rotular).observe(this.$el, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-label'] });
                },
            }));
        });
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kode+Mono:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface text-text-primary antialiased" x-data @keydown.escape.window="$store.menu.fechar()">
    <div class="flex min-h-screen">
        {{-- Fundo escurecido da gaveta (só < 768px) --}}
        <div
            x-show="$store.menu.aberto && $store.menu.modoGaveta"
            x-transition.opacity
            x-cloak
            @click="$store.menu.fechar()"
            class="fixed inset-0 z-40 bg-black/60 md:hidden"
            aria-hidden="true"
        ></div>

        <livewire:sidebar />

        <main class="flex-1 min-w-0">
            @if ($impersonador = app(\App\Services\ImpersonationService::class)->impersonador())
                <div class="sticky top-0 z-40 flex items-center justify-center gap-x-3 gap-y-1 px-3 py-1.5 sm:px-6 sm:py-2 bg-warning text-black text-xs sm:text-sm font-medium">
                    <x-heroicon-o-eye class="hidden sm:block w-4 h-4 shrink-0" />
                    <span class="min-w-0">
                        <span class="hidden sm:inline">Você ({{ $impersonador->name }}) está acessando</span><span class="sm:hidden">Acessando</span>
                        como <strong>{{ auth()->user()->name }}</strong>.
                    </span>
                    <form method="POST" action="{{ route('impersonate.stop') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="px-3 py-1 pointer-coarse:min-h-11 rounded-full bg-black/80 text-white text-xs font-semibold whitespace-nowrap hover:bg-black transition">
                            Voltar para minha conta
                        </button>
                    </form>
                </div>
            @endif

            <div
                class="sticky top-0 z-30 flex flex-wrap items-center gap-2 px-3 sm:px-6 py-2 sm:py-3 border-b border-surface-border bg-surface-card/40 backdrop-blur"
                x-data="{ light: document.documentElement.classList.contains('light') }"
            >
                <button
                    type="button"
                    @click="$store.menu.abrir()"
                    x-bind:aria-expanded="$store.menu.aberto.toString()"
                    aria-controls="menu-lateral"
                    aria-label="Abrir menu"
                    class="md:hidden p-2 pointer-coarse:min-h-11 pointer-coarse:min-w-11 flex items-center justify-center rounded-lg hover:bg-surface-border text-text-secondary hover:text-text-primary transition"
                >
                    <x-heroicon-o-bars-3 class="w-6 h-6" />
                </button>

                <div class="ml-auto flex flex-wrap items-center justify-end gap-2 min-w-0">
                    <livewire:troca-perfil />

                    <button
                        type="button"
                        @click="light = !light; document.documentElement.classList.toggle('light', light); localStorage.setItem('theme', light ? 'light' : 'dark')"
                        class="p-2 pointer-coarse:min-h-11 pointer-coarse:min-w-11 flex items-center justify-center rounded-lg hover:bg-surface-border text-text-secondary hover:text-text-primary transition"
                        title="Alternar tema claro/escuro"
                        aria-label="Alternar tema claro/escuro"
                    >
                        <x-heroicon-o-moon x-show="!light" class="w-5 h-5" />
                        <x-heroicon-o-sun x-show="light" x-cloak class="w-5 h-5" />
                    </button>

                    <livewire:notification-bell />
                </div>
            </div>

            <div class="p-4 sm:p-6">
                <div class="max-w-7xl mx-auto">
                    <x-flash-messages />

                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
