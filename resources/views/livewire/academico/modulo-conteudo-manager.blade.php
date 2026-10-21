<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">{{ $turma->nome }}</h1>
            <p class="text-sm text-text-secondary">Módulos e conteúdo</p>
        </div>
        <x-button wire:click="createModulo">+ Módulo</x-button>
    </div>

    <div class="space-y-4">
        @forelse ($modulos as $modulo)
            <x-card>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-text-primary">{{ $modulo->nome }}</h2>
                        <span class="text-xs text-text-secondary">
                            {{ $modulo->categoria === 'extra' ? 'Atividade extra' : 'Nível '.$modulo->nivel }} · ordem {{ $modulo->ordem }}
                        </span>
                    </div>
                    <div class="space-x-3 text-sm">
                        <button wire:click="createConteudo({{ $modulo->id }})" class="text-primary hover:underline">+ Conteúdo</button>
                        <button wire:click="editModulo({{ $modulo->id }})" class="text-primary hover:underline">Editar</button>
                        <button
                            wire:click="deleteModulo({{ $modulo->id }})"
                            wire:confirm="Remover este módulo e todo o conteúdo dele?"
                            class="text-danger hover:underline"
                        >
                            Excluir
                        </button>
                    </div>
                </div>

                <x-table :headers="['Título', 'Tipo', 'Ordem', 'Ações']">
                    @foreach ($modulo->conteudos as $conteudo)
                        <tr>
                            <td class="px-4 py-3">{{ $conteudo->titulo }}</td>
                            <td class="px-4 py-3">
                                <x-badge variant="primary">{{ $conteudo->tipo }}</x-badge>
                                @if ($conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo)
                                    <x-badge>{{ $conteudo->exercicio_subtipo }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-secondary">{{ $conteudo->ordem }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <a
                                    href="{{ route('academico.turmas.conteudo.previa', [$turma, $conteudo]) }}"
                                    title="Ver como o aluno vai ver esta aula"
                                    class="inline-flex items-center gap-1 text-accent hover:underline text-sm"
                                >
                                    <x-heroicon-o-eye class="w-4 h-4" /> Visualizar
                                </a>
                                <button wire:click="editConteudo({{ $conteudo->id }})" class="text-primary hover:underline text-sm">Editar</button>
                                <button
                                    wire:click="deleteConteudo({{ $conteudo->id }})"
                                    wire:confirm="Remover este conteúdo?"
                                    class="text-danger hover:underline text-sm"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @empty
            <p class="text-sm text-text-secondary">Nenhum módulo cadastrado ainda.</p>
        @endforelse
    </div>

    <x-modal name="modulo-form" title="Módulo">
        <form wire:submit="saveModulo" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="moduloNome" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('moduloNome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Categoria</label>
                <select wire:model.live="categoria" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="nivel">Nível (Básico/Intermediário/Avançado)</option>
                    <option value="extra">Atividade extra</option>
                </select>
            </div>

            @if ($categoria === 'nivel')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Nível</label>
                    <select wire:model="nivel" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        @foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $opcao)
                            <option value="{{ $opcao }}">{{ $opcao }}</option>
                        @endforeach
                    </select>
                    @error('nivel') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Seção (agrupamento na vitrine do aluno)</label>
                <input type="text" wire:model="secao" list="secoes-existentes" placeholder="ex.: Mapas Mentais, Vídeos Curtos" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                <datalist id="secoes-existentes">
                    @foreach ($secoesExistentes as $secaoExistente)
                        <option value="{{ $secaoExistente }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-text-secondary">Módulos com a mesma seção aparecem no mesmo carrossel. Em branco = "Aulas" (nível) ou "Atividades Extras".</p>
                @error('secao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Capa (JPG, PNG ou WEBP, vertical 3:4 fica melhor)</label>
                <input type="file" wire:model="capa" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-text-secondary">
                <div wire:loading wire:target="capa" class="text-xs text-text-secondary mt-1">Enviando...</div>
                @error('capa') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @if ($capaAtual && ! $capa)
                    <img src="{{ asset('storage/'.$capaAtual) }}" alt="Capa atual" class="mt-2 h-24 rounded-md object-cover">
                    <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter a capa atual.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="moduloOrdem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    @php
        $tiposConteudo = [
            'texto' => ['Texto', 'document-text'],
            'dialogo' => ['Diálogo', 'chat-bubble-left-right'],
            'video' => ['Vídeo', 'play-circle'],
            'video_curto' => ['Vídeo curto', 'film'],
            'pdf' => ['PDF', 'document-arrow-down'],
            'exercicio' => ['Exercício', 'clipboard-document-check'],
            'link' => ['Link', 'link'],
        ];
        $exemploDialogo = "Marie: Bonjour ! Comment tu t'appelles ? | Olá! Como você se chama?\nPaul: Je m'appelle Paul. Et toi ? | Eu me chamo Paul. E você?\n(Marie sorri)\nMarie: Moi, c'est Marie. Enchantée ! | Eu sou a Marie. Prazer!";
        $temExtras = in_array($tipo, \App\Models\Conteudo::TIPOS_COM_AUDIO_E_VOCABULARIO, true);
    @endphp

    <x-modal name="conteudo-form" :title="$conteudoId ? 'Editar conteúdo' : 'Novo conteúdo'" size="2xl">
        <form id="form-conteudo" wire:submit="saveConteudo" class="space-y-6">
            <div>
                <label for="conteudo-titulo" class="block text-sm font-medium mb-1 text-text-primary">Título da aula</label>
                <input id="conteudo-titulo" type="text" wire:model="titulo" placeholder="Ex.: Aula 02 — Se présenter" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('titulo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            {{-- Tipo em cartões clicáveis: o professor vê todas as opções de uma vez --}}
            <fieldset>
                <legend class="block text-sm font-medium mb-2 text-text-primary">Tipo de conteúdo</legend>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach ($tiposConteudo as $valor => [$rotulo, $icone])
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model.live="tipo" value="{{ $valor }}" class="peer sr-only">
                            <span class="flex items-center gap-2 h-full px-3 py-2.5 pointer-coarse:min-h-11 rounded-xl border text-sm transition border-surface-border text-text-secondary hover:border-primary/50 hover:text-text-primary peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-text-primary peer-checked:font-medium peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                <x-icon :name="'heroicon-o-'.$icone" class="w-5 h-5 shrink-0" />
                                {{ $rotulo }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @if ($tipo === 'texto')
                <div>
                    <label for="conteudo-corpo" class="block text-sm font-medium mb-1 text-text-primary">Texto da aula</label>
                    <textarea id="conteudo-corpo" wire:model="corpo" rows="8" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                    <p class="mt-1 text-xs text-text-secondary">As quebras de linha que você digitar aparecem iguais pro aluno.</p>
                    @error('corpo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($tipo === 'dialogo')
                {{-- Editor à esquerda, prévia à direita (empilha no celular) --}}
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <label for="dialogo-corpo" class="text-sm font-medium text-text-primary">Diálogo</label>
                            @if (trim($corpo) === '')
                                <button type="button" @click="$wire.set('corpo', @js($exemploDialogo))" class="text-xs font-medium text-primary hover:underline pointer-coarse:min-h-11">
                                    Usar exemplo
                                </button>
                            @endif
                        </div>
                        <textarea
                            id="dialogo-corpo"
                            wire:model.live.debounce.500ms="corpo"
                            rows="11"
                            placeholder="Escreva uma fala por linha…"
                            class="flex-1 w-full rounded-md bg-surface border-surface-border text-text-primary font-mono text-sm"
                        ></textarea>
                        @error('corpo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                        {{-- Guia: exemplo real (copiável) separado da explicação, pra não ser colado junto --}}
                        <div class="mt-2 rounded-lg bg-surface/60 border border-surface-border px-3 py-2 text-xs text-text-secondary">
                            <p class="font-medium text-text-primary mb-1">Como escrever — exemplo:</p>
                            <pre class="font-mono text-text-primary whitespace-pre-wrap wrap-anywhere">Marie: Bonjour ! | Olá!
John: Ça va ? - Tudo bem?
(Marie sorri)</pre>
                            <ul class="mt-2 space-y-0.5 list-disc pl-4">
                                <li>Comece a linha com o <strong class="text-text-primary">nome</strong> e dois-pontos.</li>
                                <li>A <strong class="text-text-primary">tradução</strong> é opcional: depois de <code class="font-mono text-text-primary">|</code> ou de um traço com espaços (<code class="font-mono text-text-primary"> - </code>). O aluno vê só se clicar em "Mostrar tradução".</li>
                                <li>Linha sem nome vira <strong class="text-text-primary">narração</strong>.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <p class="mb-1 text-sm font-medium text-text-primary">Prévia — como o aluno vê</p>
                        <div class="rounded-xl border border-dashed border-surface-border bg-surface/40 p-3 md:max-h-96 md:overflow-y-auto">
                            @if (trim($corpo) !== '')
                                <x-dialogo :texto="$corpo" compacto />
                            @else
                                <p class="py-10 text-center text-sm text-text-secondary">A conversa aparece aqui enquanto você escreve.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if (in_array($tipo, ['video', 'video_curto'], true))
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Link do vídeo (Google Drive, YouTube ou Vimeo)</label>
                    <input type="text" wire:model="urlExterna" placeholder="https://drive.google.com/file/d/.../view" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('urlExterna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @if ($tipo === 'video_curto')
                        <p class="mt-1 text-xs text-text-secondary">Pensado pra vídeos de 30 segundos a 1 minuto. No Google Drive, o arquivo precisa estar compartilhado como "Qualquer pessoa com o link".</p>
                    @endif
                </div>
            @endif

            @if ($tipo === 'link')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">URL externa</label>
                    <input type="text" wire:model="urlExterna" placeholder="https://..." class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('urlExterna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($tipo === 'pdf')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Arquivo PDF</label>
                    <input type="file" wire:model="arquivo" accept="application/pdf" class="w-full text-sm text-text-secondary">
                    <div wire:loading wire:target="arquivo" class="text-xs text-text-secondary mt-1">Enviando...</div>
                    @error('arquivo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @if ($conteudoId && ! $arquivo)
                        <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter o arquivo atual.</p>
                    @endif
                </div>
            @endif

            @if ($tipo === 'exercicio')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Tipo de exercício</label>
                    <select wire:model.live="exercicioSubtipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <option value="anexo">Anexo (arquivo)</option>
                        <option value="quiz">Quiz interativo</option>
                    </select>
                    @error('exercicioSubtipo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                @if ($exercicioSubtipo === 'anexo')
                    <div>
                        <label class="block text-sm font-medium mb-1 text-text-primary">Arquivo (PDF, DOC ou DOCX)</label>
                        <input type="file" wire:model="arquivo" class="w-full text-sm text-text-secondary">
                        <div wire:loading wire:target="arquivo" class="text-xs text-text-secondary mt-1">Enviando...</div>
                        @error('arquivo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        @if ($conteudoId && ! $arquivo)
                            <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter o arquivo atual.</p>
                        @endif
                    </div>
                @endif

                @if ($exercicioSubtipo === 'quiz')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-text-primary">Perguntas</label>
                            <button type="button" wire:click="adicionarPergunta" class="text-primary hover:underline text-sm">+ Pergunta</button>
                        </div>
                        @error('perguntas') <p class="text-sm text-danger">{{ $message }}</p> @enderror

                        @foreach ($perguntas as $i => $pergunta)
                            <div class="rounded-lg border border-surface-border p-3 space-y-3">
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <input
                                            type="text"
                                            wire:model="perguntas.{{ $i }}.enunciado"
                                            placeholder="Enunciado da pergunta"
                                            class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm"
                                        >
                                        @error("perguntas.{$i}.enunciado") <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="button" wire:click="removerPergunta({{ $i }})" class="text-danger hover:underline text-xs mt-2">Remover</button>
                                </div>

                                <div class="pl-3 space-y-2">
                                    @error("perguntas.{$i}.opcoes") <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                    @foreach ($pergunta['opcoes'] as $j => $opcao)
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" wire:model="perguntas.{{ $i }}.opcoes.{{ $j }}.correta" class="rounded bg-surface border-surface-border" title="Opção correta">
                                            <input
                                                type="text"
                                                wire:model="perguntas.{{ $i }}.opcoes.{{ $j }}.texto"
                                                placeholder="Texto da opção"
                                                class="flex-1 rounded-md bg-surface border-surface-border text-text-primary text-sm"
                                            >
                                            <button type="button" wire:click="removerOpcao({{ $i }}, {{ $j }})" class="text-danger hover:underline text-xs">Remover</button>
                                        </div>
                                        @error("perguntas.{$i}.opcoes.{$j}.texto") <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                    @endforeach

                                    <button type="button" wire:click="adicionarOpcao({{ $i }})" class="text-primary hover:underline text-xs">+ Opção</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif


            @if ($temExtras)
                {{-- Recursos extras: recolhível, abre sozinho se já tiver áudio ou vocabulário --}}
                <section x-data="{ aberto: @js((bool) ($audioAtual || trim($vocabulario) !== '')) }" class="rounded-xl border border-surface-border">
                    <button type="button" @click="aberto = ! aberto" x-bind:aria-expanded="aberto.toString()" class="w-full flex items-center justify-between gap-3 px-4 py-3 pointer-coarse:min-h-11 text-left">
                        <span class="flex flex-wrap items-center gap-x-2 text-sm font-medium text-text-primary">
                            <x-heroicon-o-sparkles class="w-5 h-5 text-accent" />
                            Recursos extras <span class="font-normal text-text-secondary">(opcional) — áudio e vocabulário</span>
                        </span>
                        <x-heroicon-o-chevron-down class="w-4 h-4 shrink-0 text-text-secondary transition-transform" x-bind:class="aberto && 'rotate-180'" />
                    </button>

                    <div x-show="aberto" x-cloak class="grid gap-4 md:grid-cols-2 px-4 pb-4">
                        <div class="min-w-0">
                            <label for="conteudo-audio" class="block text-sm font-medium mb-1 text-text-primary">Áudio</label>
                            <input id="conteudo-audio" type="file" wire:model="audio" accept="audio/*" class="w-full text-sm text-text-secondary file:mr-3 file:rounded-full file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-primary">
                            <div wire:loading wire:target="audio" class="text-xs text-text-secondary mt-1">Enviando...</div>
                            @error('audio') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-text-secondary">MP3, M4A, WAV ou OGG, até 20 MB — ex.: a gravação do diálogo pra o aluno ouvir a pronúncia.</p>

                            @if ($audioAtual && ! $audio)
                                <div class="mt-2 space-y-2">
                                    <audio controls preload="none" src="{{ asset('storage/'.$audioAtual) }}" class="w-full h-9 {{ $removerAudio ? 'opacity-40' : '' }}"></audio>
                                    <label class="flex items-center gap-2 text-xs text-text-secondary">
                                        <input type="checkbox" wire:model.live="removerAudio" class="rounded bg-surface border-surface-border">
                                        Remover áudio atual
                                    </label>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <label for="conteudo-vocabulario" class="block text-sm font-medium mb-1 text-text-primary">Vocabulário</label>
                            <textarea
                                id="conteudo-vocabulario"
                                wire:model="vocabulario"
                                rows="5"
                                placeholder="Ex.: bonjour | olá"
                                class="w-full rounded-md bg-surface border-surface-border text-text-primary font-mono text-sm"
                            ></textarea>
                            <p class="mt-1 text-xs text-text-secondary">Uma palavra por linha: <code class="font-mono text-text-primary">bonjour | olá</code> ou <code class="font-mono text-text-primary">bonjour - olá</code>. Aparece no fim da aula.</p>
                            @error('vocabulario') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            @endif

            {{-- Publicação: quando e se o aluno vê --}}
            <section class="rounded-xl bg-surface/50 border border-surface-border p-4">
                <h3 class="flex items-center gap-2 text-sm font-medium text-text-primary mb-3">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-primary" /> Publicação
                </h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="conteudo-ordem" class="block text-xs font-medium mb-1 text-text-secondary">Ordem no módulo</label>
                        <input id="conteudo-ordem" type="number" min="0" wire:model="conteudoOrdem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        @error('conteudoOrdem') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="conteudo-dias" class="block text-xs font-medium mb-1 text-text-secondary">Liberar após (dias da matrícula)</label>
                        <input id="conteudo-dias" type="number" min="0" wire:model="diasLiberacao" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <p class="mt-1 text-xs text-text-secondary">0 = libera na hora.</p>
                        @error('diasLiberacao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-start gap-2 sm:pt-6 text-sm text-text-secondary cursor-pointer">
                        <input type="checkbox" wire:model="bloqueado" class="mt-0.5 rounded bg-surface border-surface-border">
                        <span><span class="font-medium text-text-primary">Bloquear</span><br><span class="text-xs">Alunos não abrem, mesmo depois do prazo.</span></span>
                    </label>
                </div>
            </section>
        </form>

        <x-slot:footer>
            <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
            <x-button type="submit" form="form-conteudo" wire:loading.attr="disabled" wire:target="saveConteudo,audio,arquivo">Salvar conteúdo</x-button>
        </x-slot:footer>
    </x-modal>
</div>
