@props(['headers' => []])

{{-- Rola na horizontal quando não cabe; < 640px cada linha vira um card (ver .tabela-responsiva em app.css). --}}
<div x-data="tabelaResponsiva" {{ $attributes->merge(['class' => 'tabela-responsiva bg-surface-card border border-surface-border rounded-xl overflow-x-auto']) }}>
    <table class="w-full text-sm text-left">
        @if (count($headers))
            <thead class="bg-surface text-text-secondary">
                <tr>
                    @foreach ($headers as $header)
                        <th class="px-4 py-3 font-medium whitespace-nowrap">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-surface-border text-text-primary">
            {{ $slot }}
        </tbody>
    </table>
</div>
