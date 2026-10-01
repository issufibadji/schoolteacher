<?php

namespace App\Notifications;

use App\Models\AulaAoVivo;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class AulaAoVivoLembrete extends Notification
{
    public const PREVIO = 'previo';

    public const INICIO = 'inicio';

    public function __construct(private AulaAoVivo $aula, private string $tipo) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    private function titulo(): string
    {
        return $this->tipo === self::PREVIO ? 'Aula ao vivo já já' : 'Aula ao vivo na hora';
    }

    private function mensagem(object $notifiable): string
    {
        $professor = $notifiable->id === $this->aula->turma->professor_id;
        $quando = $this->tipo === self::PREVIO
            ? 'começa às '.$this->aula->inicio_em->format('H:i')
            : 'está marcada pra agora';

        $acao = match (true) {
            $this->tipo === self::PREVIO => '',
            $professor => ' Abra a página Aulas ao vivo e clique em Iniciar.',
            default => ' Entre pela página Aulas ao Vivo assim que o professor abrir a sala.',
        };

        return "{$this->aula->titulo} — {$this->aula->turma->nome} {$quando}.{$acao}";
    }

    public function toDatabase(object $notifiable): array
    {
        return ['title' => $this->titulo(), 'message' => $this->mensagem($notifiable)];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)->title($this->titulo())->body($this->mensagem($notifiable));
    }
}
