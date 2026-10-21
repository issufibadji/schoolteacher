{{--
    Diálogo em balões de conversa, a partir do texto "Personagem: frase | tradução".
    - compacto=false (aluno/prévia): barra com "Mostrar tradução" (começa escondida)
      e "Praticar como <personagem>" (esconde as falas dele pra o aluno falar em voz alta).
    - compacto=true (prévia ao vivo no formulário do professor): só os balões, tradução à mostra.
--}}
@props(['texto' => '', 'compacto' => false])

@php
    $falas = \App\Models\Conteudo::interpretarDialogo($texto);
    $personagens = \App\Models\Conteudo::personagensDoDialogo($falas);
    $temTraducao = collect($falas)->contains(fn ($f) => $f['traducao']);

    // Cor por personagem, na ordem em que aparecem; lado alterna (1º à esquerda, 2º à direita...).
    $cores = [
        ['balao' => 'bg-primary/10 border-primary/30', 'nome' => 'text-primary'],
        ['balao' => 'bg-accent/10 border-accent/30', 'nome' => 'text-accent'],
        ['balao' => 'bg-success/10 border-success/30', 'nome' => 'text-success'],
        ['balao' => 'bg-warning/10 border-warning/30', 'nome' => 'text-warning'],
    ];
    $estilo = fn (?string $p) => $cores[array_search($p, $personagens, true) % count($cores)];
    $aDireita = fn (?string $p) => array_search($p, $personagens, true) % 2 === 1;
@endphp

<div
    x-data="{ traducao: {{ $compacto ? 'true' : 'false' }}, papel: null, revelados: {} }"
    x-effect="papel; revelados = {}"
    {{ $attributes->merge(['class' => 'space-y-4']) }}
>
    @unless ($compacto)
        @if ($temTraducao || count($personagens) > 1)
            <div class="flex flex-wrap items-center gap-2">
                @if ($temTraducao)
                    <button
                        type="button"
                        @click="traducao = ! traducao"
                        x-bind:aria-pressed="traducao.toString()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 pointer-coarse:min-h-11 rounded-full text-xs font-medium border border-surface-border transition"
                        x-bind:class="traducao ? 'bg-primary text-white border-primary' : 'text-text-secondary hover:text-text-primary hover:bg-surface-border/60'"
                    >
                        <x-heroicon-o-language class="w-4 h-4" />
                        <span x-text="traducao ? 'Esconder tradução' : 'Mostrar tradução'">Mostrar tradução</span>
                    </button>
                @endif

                @if (count($personagens) > 1)
                    <span class="text-xs text-text-secondary ml-1">Praticar como:</span>
                    @foreach ($personagens as $personagem)
                        <button
                            type="button"
                            @click="papel = papel === @js($personagem) ? null : @js($personagem)"
                            x-bind:aria-pressed="(papel === @js($personagem)).toString()"
                            class="px-3 py-1.5 pointer-coarse:min-h-11 rounded-full text-xs font-medium border border-surface-border transition"
                            x-bind:class="papel === @js($personagem) ? 'bg-accent text-white border-accent' : 'text-text-secondary hover:text-text-primary hover:bg-surface-border/60'"
                        >
                            {{ $personagem }}
                        </button>
                    @endforeach
                @endif
            </div>

            <p x-show="papel" x-cloak class="text-xs text-text-secondary">
                Modo prática: as falas de <strong class="text-text-primary" x-text="papel"></strong> estão escondidas.
                Leia as outras falas e diga a sua em voz alta antes de revelar.
            </p>
        @endif
    @endunless

    <div class="space-y-3">
        @forelse ($falas as $i => $fala)
            @if ($fala['personagem'] === null)
                {{-- Narração / rubrica: linha sem "Nome:" --}}
                <p class="text-center text-sm italic text-text-secondary wrap-anywhere">
                    {{ $fala['texto'] }}
                    @if ($fala['traducao'])
                        <span x-show="traducao" class="block not-italic text-xs mt-0.5">{{ $fala['traducao'] }}</span>
                    @endif
                </p>
            @else
                @php $cor = $estilo($fala['personagem']); @endphp
                <div class="flex {{ $aDireita($fala['personagem']) ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[88%] sm:max-w-[75%] min-w-0 rounded-2xl border px-4 py-3 {{ $cor['balao'] }} {{ $aDireita($fala['personagem']) ? 'rounded-br-sm' : 'rounded-bl-sm' }}">
                        <p class="text-xs font-semibold mb-1 {{ $cor['nome'] }} {{ $aDireita($fala['personagem']) ? 'text-right' : '' }}">{{ $fala['personagem'] }}</p>

                        @php $minhaFala = 'papel === '.\Illuminate\Support\Js::from($fala['personagem']).' && ! revelados['.$i.']'; @endphp

                        <div x-show="{{ $minhaFala }}" x-cloak class="flex flex-wrap items-center gap-2">
                            <span class="text-sm italic text-text-secondary">Sua vez — fale em voz alta.</span>
                            <button type="button" @click="revelados[{{ $i }}] = true" class="text-xs font-medium text-primary hover:underline pointer-coarse:min-h-11">
                                Revelar
                            </button>
                        </div>

                        <div x-show="! ({{ $minhaFala }})">
                            <p class="text-base leading-relaxed text-text-primary wrap-anywhere">{{ $fala['texto'] }}</p>
                            @if ($fala['traducao'])
                                <p x-show="traducao" x-cloak class="mt-1 text-sm italic text-text-secondary wrap-anywhere">{{ $fala['traducao'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <p class="text-sm text-text-secondary">Nenhuma fala ainda.</p>
        @endforelse
    </div>
</div>
