@props(['name', 'title' => null])

{{--
    Fecha no fundo só se o mouse foi apertado E solto no fundo. Sem isso,
    selecionar texto num campo e soltar o mouse fora do card (ou o contrário)
    gera um "click" no fundo e fecha o modal no meio do preenchimento.
--}}
<div
    x-data="{ open: false, pressionouFundo: false, soltouFundo: false }"
    x-on:open-modal.window="$event.detail.name === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-on:mousedown="pressionouFundo = $event.target === $el"
    x-on:mouseup="soltouFundo = $event.target === $el"
    x-on:click="if (pressionouFundo && soltouFundo) open = false; pressionouFundo = soltouFundo = false"
>
    <div x-show="open" x-transition.opacity class="pointer-events-none absolute inset-0 bg-black/60"></div>

    <div
        x-show="open"
        x-transition
        role="dialog"
        aria-modal="true"
        class="relative w-full max-w-lg max-h-[calc(100dvh-2rem)] overflow-y-auto bg-surface-card border border-surface-border rounded-xl p-6"
    >
        @if ($title)
            <h2 class="text-lg font-semibold text-text-primary mb-4">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </div>
</div>
