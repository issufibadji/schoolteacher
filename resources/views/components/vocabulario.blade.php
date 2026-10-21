{{-- Lista de vocabulário da aula ("palavra | tradução", um por linha). Traduções podem ser escondidas pra o aluno se testar. --}}
@props(['texto' => ''])

@php $itens = \App\Models\Conteudo::interpretarVocabulario($texto); @endphp

@if ($itens)
    <div x-data="{ traducao: true }" {{ $attributes->merge(['class' => 'glow-border bg-surface-card/80 backdrop-blur border border-surface-border rounded-2xl p-6']) }}>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h2 class="flex items-center gap-2 text-base font-semibold text-text-primary">
                <x-heroicon-o-book-open class="w-5 h-5 text-primary" /> Vocabulário da aula
            </h2>
            @if (collect($itens)->contains(fn ($i) => $i['traducao']))
                <button type="button" @click="traducao = ! traducao" class="text-xs font-medium text-primary hover:underline pointer-coarse:min-h-11">
                    <span x-text="traducao ? 'Esconder traduções' : 'Mostrar traduções'">Esconder traduções</span>
                </button>
            @endif
        </div>

        <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-2">
            @foreach ($itens as $item)
                <div class="flex flex-wrap items-baseline justify-between gap-x-3 border-b border-surface-border/60 pb-2 min-w-0">
                    <dt class="font-medium text-text-primary wrap-anywhere">{{ $item['termo'] }}</dt>
                    @if ($item['traducao'])
                        <dd x-show="traducao" class="text-sm text-text-secondary wrap-anywhere">{{ $item['traducao'] }}</dd>
                    @endif
                </div>
            @endforeach
        </dl>
    </div>
@endif
