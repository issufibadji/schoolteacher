{{--
    < 768px: gaveta sobreposta (off-canvas), aberta pelo botão do topo; foco preso
    enquanto aberta e inerte enquanto fechada. >= 768px: no fluxo, recolhível.
    Estado em Alpine.store('menu') (layouts/master).
--}}
<aside
    id="menu-lateral"
    x-data="{ get collapsed() { return this.$store.menu.compacto } }"
    x-trap.noscroll="$store.menu.aberto && $store.menu.modoGaveta"
    x-bind:inert="$store.menu.modoGaveta && ! $store.menu.aberto"
    x-bind:class="[$store.menu.aberto && 'max-md:translate-x-0!', collapsed ? 'md:w-20' : 'md:w-64']"
    @click="$event.target.closest('a[href]') && $store.menu.fechar()"
    aria-label="Menu principal"
    class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] max-md:-translate-x-full md:sticky md:top-0 md:z-auto md:max-w-none shrink-0 bg-surface-card md:bg-surface-card/70 backdrop-blur border-r border-surface-border flex flex-col h-dvh md:h-screen transition-[width,translate] duration-200"
>
    <div class="px-5 py-5 flex items-center gap-3" x-bind:class="collapsed && 'justify-center px-0'">
        <div class="w-9 h-9 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30 shrink-0 overflow-hidden">
            <x-app-logo />
        </div>
        <span x-show="!collapsed" x-cloak class="flex-1 min-w-0 text-lg font-semibold text-text-primary whitespace-nowrap truncate">{{ config('app.name') }}</span>

        <button
            type="button"
            @click="$store.menu.fechar()"
            aria-label="Fechar menu"
            class="md:hidden -mr-2 p-2 pointer-coarse:min-h-11 pointer-coarse:min-w-11 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-border hover:text-text-primary transition"
        >
            <x-heroicon-o-x-mark class="w-6 h-6" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 space-y-1">
        @foreach ($groups as $group => $groupItems)
            @if ($group)
                <p
                    x-show="!collapsed"
                    x-cloak
                    class="px-3 {{ $loop->first ? 'pt-1' : 'pt-4' }} pb-1 text-[11px] font-semibold uppercase tracking-wider text-text-secondary/70 whitespace-nowrap"
                >
                    {{ $group }}
                </p>
                @unless ($loop->first)
                    <div x-show="collapsed" x-cloak class="border-t border-surface-border mx-2 my-2"></div>
                @endunless
            @endif

            @foreach ($groupItems as $item)
            @if ($item->children->isNotEmpty())
                <div x-data="{ open: {{ $item->children->contains(fn ($child) => request()->routeIs($child->route_name)) ? 'true' : 'false' }} }">
                    <button
                        @click="collapsed ? ($store.menu.expandir(), open = true) : (open = !open)"
                        type="button"
                        title="{{ $item->label }}"
                        x-bind:aria-expanded="open.toString()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 pointer-coarse:min-h-11 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
                        x-bind:class="collapsed && 'justify-center px-0'"
                    >
                        @if ($item->hasValidIcon())
                            <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                        @endif
                        <span x-show="!collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">{{ $item->label }}</span>
                        <x-heroicon-o-chevron-down x-show="!collapsed" x-cloak class="w-4 h-4 shrink-0 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                    </button>

                    <div x-show="open && !collapsed" x-transition class="ml-8 mt-1 space-y-1">
                        @foreach ($item->children as $child)
                            <a
                                href="{{ route($child->route_name) }}"
                                class="block px-3 py-2 pointer-coarse:py-3 rounded-xl text-sm transition {{ request()->routeIs($child->route_name) ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:bg-surface-border/60 hover:text-text-primary' }}"
                            >
                                {{ $child->label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($item->route_name) }}"
                    title="{{ $item->label }}"
                    class="flex items-center gap-3 px-3 py-2.5 pointer-coarse:min-h-11 rounded-xl text-sm transition {{ request()->routeIs($item->route_name) ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:bg-surface-border/60 hover:text-text-primary' }}"
                    x-bind:class="collapsed && 'justify-center px-0'"
                >
                    @if ($item->hasValidIcon())
                        <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                    @endif
                    <span x-show="!collapsed" x-cloak class="whitespace-nowrap">{{ $item->label }}</span>
                </a>
            @endif
            @endforeach
        @endforeach
    </nav>

    {{-- Recolher só faz sentido com a barra no fluxo (>= 768px) --}}
    <div class="hidden md:block px-3 py-3 border-t border-surface-border">
        <button
            type="button"
            @click="$store.menu.alternarRecolhido()"
            title="{{ __('Recolher menu') }}"
            x-bind:aria-expanded="(! collapsed).toString()"
            aria-controls="menu-lateral"
            class="w-full flex items-center gap-3 px-3 py-2 pointer-coarse:min-h-11 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
            x-bind:class="collapsed && 'justify-center px-0'"
        >
            <x-heroicon-o-chevron-double-left x-show="!collapsed" class="w-5 h-5 shrink-0" />
            <x-heroicon-o-chevron-double-right x-show="collapsed" x-cloak class="w-5 h-5 shrink-0" />
            <span x-show="!collapsed" x-cloak class="whitespace-nowrap">Recolher menu</span>
        </button>
    </div>

    <div class="px-3 py-4 border-t border-surface-border">
        <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-3 py-2 pointer-coarse:min-h-11 rounded-xl hover:bg-surface-border/60 transition" x-bind:class="collapsed && 'justify-center px-0'">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-linear-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-semibold shrink-0">
                @if (auth()->user()->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="w-full h-full object-cover" alt="Avatar">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <span x-show="!collapsed" x-cloak class="text-sm text-text-secondary truncate whitespace-nowrap">{{ auth()->user()->name }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                title="Sair"
                class="w-full flex items-center gap-3 px-3 py-2 pointer-coarse:min-h-11 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
                x-bind:class="collapsed && 'justify-center px-0'"
            >
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="whitespace-nowrap">Sair</span>
            </button>
        </form>
    </div>
</aside>
