@props(['name', 'title' => null, 'size' => 'lg'])

{{--
    Fecha no fundo só se o mouse foi apertado E solto no fundo. Sem isso,
    selecionar texto num campo e soltar o mouse fora do card (ou o contrário)
    gera um "click" no fundo e fecha o modal no meio do preenchimento.

    size: lg (padrão, formulários curtos) | xl | 2xl (formulários com editor + prévia).
    Slot "footer" opcional: fica fixo embaixo (botões sempre visíveis) e só o meio rola.
    Num footer fora do <form>, use form="id-do-form" no botão de envio.
--}}
@php
    $largura = match ($size) {
        'xl' => 'max-w-2xl',
        '2xl' => 'max-w-4xl',
        default => 'max-w-lg',
    };
@endphp

<div
    x-data="{ open: false, pressionouFundo: false, soltouFundo: false }"
    x-on:open-modal.window="$event.detail.name === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
    x-on:mousedown="pressionouFundo = $event.target === $el"
    x-on:mouseup="soltouFundo = $event.target === $el"
    x-on:click="if (pressionouFundo && soltouFundo) open = false; pressionouFundo = soltouFundo = false"
>
    <div x-show="open" x-transition.opacity class="pointer-events-none absolute inset-0 bg-black/60"></div>

    @isset($footer)
        <div
            x-show="open"
            x-transition
            role="dialog"
            aria-modal="true"
            @if ($title) aria-label="{{ $title }}" @endif
            class="relative w-full {{ $largura }} max-h-[calc(100dvh-1rem)] sm:max-h-[calc(100dvh-2rem)] flex flex-col bg-surface-card border border-surface-border rounded-xl shadow-2xl"
        >
            @if ($title)
                <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 border-b border-surface-border">
                    <h2 class="text-lg font-semibold text-text-primary">{{ $title }}</h2>
                    <button type="button" @click="open = false" aria-label="Fechar" class="p-1.5 pointer-coarse:min-h-11 pointer-coarse:min-w-11 flex items-center justify-center rounded-lg text-text-secondary hover:bg-surface-border hover:text-text-primary transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
            @endif

            <div class="flex-1 min-h-0 overflow-y-auto px-5 sm:px-6 py-5">
                {{ $slot }}
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 px-5 sm:px-6 py-3 border-t border-surface-border bg-surface-card rounded-b-xl">
                {{ $footer }}
            </div>
        </div>
    @else
        <div
            x-show="open"
            x-transition
            role="dialog"
            aria-modal="true"
            class="relative w-full {{ $largura }} max-h-[calc(100dvh-2rem)] overflow-y-auto bg-surface-card border border-surface-border rounded-xl p-6"
        >
            @if ($title)
                <h2 class="text-lg font-semibold text-text-primary mb-4">{{ $title }}</h2>
            @endif

            {{ $slot }}
        </div>
    @endisset
</div>
