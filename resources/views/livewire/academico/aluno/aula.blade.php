@php
    // $previa: professor vendo a aula como o aluno (PreviaConteudo). Sempre liberada,
    // sem progresso/quiz salvos, com gabarito e regra de liberação à mostra.
    $previa = $previa ?? false;
    $rotaAula = $previa ? 'academico.turmas.conteudo.previa' : 'academico.minha-turma.aula';
@endphp

<div class="max-w-4xl mx-auto min-w-0">
    @if ($previa)
        <div class="mb-6 rounded-2xl border border-accent/40 bg-accent/10 p-4 flex flex-wrap items-start gap-3">
            <x-heroicon-o-eye class="w-5 h-5 shrink-0 text-accent mt-0.5" />
            <div class="flex-1 min-w-0 text-sm">
                <p class="font-semibold text-text-primary">Pré-visualização — é assim que o aluno vê esta aula.</p>
                <p class="text-text-secondary mt-0.5">
                    @if ($conteudo->bloqueado)
                        <span class="text-danger font-medium">Bloqueada manualmente:</span> os alunos ainda não conseguem abrir.
                    @elseif ($conteudo->dias_liberacao > 0)
                        Liberada para o aluno {{ $conteudo->dias_liberacao }} dia(s) após a matrícula.
                    @else
                        Liberada para o aluno logo após a matrícula.
                    @endif
                    Nada do que você fizer aqui conta como progresso.
                </p>
            </div>
            <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="shrink-0 text-sm font-medium text-primary hover:underline">
                Voltar à edição
            </a>
        </div>
    @endif

    <div class="mb-6">
        @if ($previa)
            <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="text-sm text-primary hover:underline">&larr; {{ $turma->nome }} · {{ $modulo->nome }}</a>
        @else
            <a href="{{ route('academico.minha-turma.modulo', [$turma, $modulo]) }}" class="text-sm text-primary hover:underline">&larr; {{ $modulo->nome }}</a>
        @endif
        <div class="flex items-center gap-3 mt-2 flex-wrap">
            <h1 class="text-2xl font-semibold text-text-primary">{{ $conteudo->titulo }}</h1>
            <x-badge variant="primary">{{ $conteudo->rotuloTipo() }}</x-badge>
            @if ($concluido)
                <span class="inline-flex items-center gap-1 text-sm text-success">
                    <x-heroicon-s-check-circle class="w-4 h-4" /> Concluída
                </span>
            @endif
        </div>
    </div>

    @if (! $disponivel)
        <x-card>
            <div class="flex flex-col items-center text-center py-10 gap-2">
                <x-heroicon-o-lock-closed class="w-10 h-10 text-text-secondary" />
                <p class="text-text-primary font-medium">
                    {{ $diasRestantes > 0 ? "Esta aula libera em {$diasRestantes} dia(s)." : 'Esta aula está bloqueada no momento.' }}
                </p>
            </div>
        </x-card>
    @else
        <div class="space-y-4">
            @switch(true)
                @case(in_array($conteudo->tipo, ['video', 'video_curto'], true))
                    @if ($conteudo->embedUrlVideo())
                        <iframe src="{{ $conteudo->embedUrlVideo() }}" class="w-full aspect-video rounded-2xl border border-surface-border bg-black" allow="autoplay; fullscreen" allowfullscreen></iframe>
                    @elseif ($conteudo->url_externa)
                        <x-card>
                            <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">Abrir vídeo &rarr;</a>
                        </x-card>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum vídeo cadastrado ainda.</p></x-card>
                    @endif
                    @break

                @case($conteudo->tipo === 'texto')
                    <x-card>
                        {{-- wrap-anywhere: palavra/link sem espaço (ex.: "kkkk…", URL longa) quebra em vez de vazar do card --}}
                        <div class="text-base leading-relaxed text-text-primary whitespace-pre-line wrap-anywhere">{{ $conteudo->corpo ?? 'Nenhum conteúdo cadastrado ainda.' }}</div>
                    </x-card>
                    @break

                @case($conteudo->tipo === 'link')
                    <x-card>
                        @if ($conteudo->url_externa)
                            <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">Abrir link externo &rarr;</a>
                        @else
                            <p class="text-sm text-text-secondary">Nenhum link cadastrado ainda.</p>
                        @endif
                    </x-card>
                    @break

                @case($ehQuiz)
                    @if ($concluido)
                        <x-card>
                            <p class="text-text-primary font-medium">Sua nota: {{ $score }}%</p>
                            <button type="button" wire:click="refazerQuiz" class="text-primary hover:underline text-sm mt-2">Refazer o quiz</button>
                        </x-card>
                    @else
                        <div class="space-y-4">
                            @foreach ($conteudo->perguntas as $pergunta)
                                <x-card>
                                    <p class="text-text-primary font-medium mb-3 whitespace-pre-line wrap-anywhere">{{ $pergunta->enunciado }}</p>
                                    <div class="space-y-2">
                                        @foreach ($pergunta->opcoes as $opcao)
                                            <label class="flex items-start gap-2 text-sm text-text-secondary">
                                                @if ($previa)
                                                    {{-- Na prévia o professor vê o gabarito; nada é marcado nem salvo. --}}
                                                    <input type="checkbox" disabled @checked($opcao->correta) class="mt-0.5 shrink-0 rounded bg-surface border-surface-border">
                                                @else
                                                    <input type="checkbox" wire:model="respostasSelecionadas.{{ $pergunta->id }}.{{ $opcao->id }}" class="mt-0.5 shrink-0 rounded bg-surface border-surface-border">
                                                @endif
                                                <span class="min-w-0 wrap-anywhere">{{ $opcao->texto }}</span>
                                                @if ($previa && $opcao->correta)
                                                    <x-badge variant="success">correta</x-badge>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </x-card>
                            @endforeach
                            @if ($previa)
                                <p class="text-xs text-text-secondary">O aluno marca as opções e clica em "Enviar respostas". As corretas estão destacadas só pra você.</p>
                            @else
                                <x-button wire:click="enviarQuiz">Enviar respostas</x-button>
                            @endif
                        </div>
                    @endif
                    @break

                @case($conteudo->tipo === 'pdf')
                    @if ($conteudo->arquivo_path)
                        <div class="rounded-2xl overflow-hidden border border-surface-border bg-surface-card">
                            <iframe src="{{ asset('storage/'.$conteudo->arquivo_path) }}" class="w-full h-[75vh]"></iframe>
                        </div>
                        <div class="flex justify-end">
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Baixar PDF
                            </a>
                        </div>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum arquivo anexado ainda.</p></x-card>
                    @endif
                    @break

                @default
                    {{-- exercício-anexo: pdf, doc ou docx. Doc/docx não renderiza no navegador, mas
                         se o professor anexou um PDF aqui, mostra o mesmo visualizador embutido. --}}
                    @if ($conteudo->arquivo_path && str_ends_with(strtolower($conteudo->arquivo_path), '.pdf'))
                        <div class="rounded-2xl overflow-hidden border border-surface-border bg-surface-card">
                            <iframe src="{{ asset('storage/'.$conteudo->arquivo_path) }}" class="w-full h-[75vh]"></iframe>
                        </div>
                        <div class="flex justify-end">
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Baixar PDF
                            </a>
                        </div>
                    @elseif ($conteudo->arquivo_path)
                        <x-card>
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Abrir / baixar arquivo
                            </a>
                        </x-card>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum arquivo anexado ainda.</p></x-card>
                    @endif
            @endswitch

            @unless ($ehQuiz || $previa)
                <div class="flex justify-end">
                    <x-button wire:click="toggleConclusao" :variant="$concluido ? 'secondary' : 'primary'">
                        {{ $concluido ? 'Concluída ✓ (desmarcar)' : 'Marcar como concluída' }}
                    </x-button>
                </div>
            @endunless
        </div>
    @endif

    <div class="flex items-center justify-between mt-8 pt-4 border-t border-surface-border text-sm">
        @if ($vizinhos['anterior'])
            <a href="{{ route($rotaAula, [$turma, $vizinhos['anterior']]) }}" class="text-primary hover:underline">&larr; Aula anterior</a>
        @else
            <span class="text-text-secondary/50">&larr; Aula anterior</span>
        @endif

        @if ($vizinhos['proximo'])
            <a href="{{ route($rotaAula, [$turma, $vizinhos['proximo']]) }}" class="text-primary hover:underline">Próxima aula &rarr;</a>
        @else
            <span class="text-text-secondary/50">Próxima aula &rarr;</span>
        @endif
    </div>
</div>
