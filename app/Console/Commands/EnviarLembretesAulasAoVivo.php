<?php

namespace App\Console\Commands;

use App\Models\AulaAoVivo;
use App\Notifications\AulaAoVivoLembrete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EnviarLembretesAulasAoVivo extends Command
{
    protected $signature = 'aulas:lembretes';

    protected $description = 'Avisa alunos e professor quando uma aula ao vivo agendada está perto de começar e na hora marcada';

    public function handle(): int
    {
        $antecedencia = config('services.jitsi.lembrete_minutos');
        $enviados = 0;

        AulaAoVivo::with('turma.alunos', 'turma.professor')
            ->where('status', AulaAoVivo::AGENDADA)
            ->get()
            ->each(function (AulaAoVivo $aula) use ($antecedencia, &$enviados) {
                $minutos = $aula->minutosParaComecar();

                // Passou da janela da aula sem ninguém iniciar: não avisa mais.
                if ($minutos < -$aula->duracao_minutos) {
                    return;
                }

                if ($minutos > 0 && $minutos <= $antecedencia && ! $aula->lembrete_previo_em) {
                    $this->avisar($aula, AulaAoVivoLembrete::PREVIO);
                    $aula->update(['lembrete_previo_em' => now()]);
                    $enviados++;
                }

                if ($minutos <= 0 && ! $aula->lembrete_inicio_em) {
                    $this->avisar($aula, AulaAoVivoLembrete::INICIO);
                    $aula->update(['lembrete_inicio_em' => now(), 'lembrete_previo_em' => $aula->lembrete_previo_em ?? now()]);
                    $enviados++;
                }
            });

        $this->info("Lembretes enviados: {$enviados}");

        return self::SUCCESS;
    }

    private function avisar(AulaAoVivo $aula, string $tipo): void
    {
        $destinatarios = $aula->turma->alunos->push($aula->turma->professor)->filter()->unique('id');

        Notification::send($destinatarios, new AulaAoVivoLembrete($aula, $tipo));
    }
}
