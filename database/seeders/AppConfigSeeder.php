<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Seeder;

class AppConfigSeeder extends Seeder
{
    /**
     * Chaves de Configurações que o sistema lê. Textos nascem com o padrão no
     * campo Valor, pra ficar claro onde editar; imagens vão no campo Mídia.
     * Sem valor/mídia, cada tela cai no seu padrão. Pode rodar de novo à vontade:
     * só cria o que falta e só preenche Valor vazio, sem tocar no que o admin editou.
     */
    public function run(): void
    {
        $configs = [
            // Identidade do sistema
            'app_nome' => [
                'value' => config('app.name'),
                'description' => 'Nome do sistema (menu, títulos das abas, login, e-mails e app autenticador do 2FA) — edite no campo Valor. Vazio: usa o APP_NAME do .env.',
            ],
            'app_logo' => [
                'value' => null,
                'description' => 'Logo do sistema (menu lateral e login) — envie no campo Mídia, de preferência quadrada e sem fundo. Sem mídia, usa o ícone padrão.',
            ],
            'app_favicon' => [
                'value' => null,
                'description' => 'Ícone da aba do navegador — envie no campo Mídia (PNG quadrado, ex.: 64x64). Sem mídia, usa o favicon.ico padrão.',
            ],
            'app_rodape' => [
                'value' => null,
                'description' => 'Texto do rodapé das telas de login — edite no campo Valor. Vazio: "© {ano} {nome do sistema}. Todos os direitos reservados."',
            ],

            // Tela de login
            'login_imagem' => [
                'value' => null,
                'description' => 'Imagem do painel esquerdo do login — envie no campo Mídia (PNG sem fundo). Sem mídia, usa a ilustração padrão.',
            ],
            'login_subtitulo' => [
                'value' => 'Bem-vindo',
                'description' => 'Texto pequeno acima do título do login — edite no campo Valor.',
            ],
            'login_titulo' => [
                'value' => 'Gestão inteligente, em um só lugar.',
                'description' => 'Título do painel esquerdo do login — edite no campo Valor.',
            ],

            // Integrações
            'webhook_url' => [
                'value' => null,
                'description' => 'URL que recebe as notificações do sistema via POST (canal Webhook) — edite no campo Valor. Vazio: webhook desligado.',
            ],
        ];

        foreach ($configs as $key => $dados) {
            $config = AppConfig::firstOrCreate(['key' => $key], $dados);

            // Linha antiga sem valor: preenche com o padrão, sem tocar no que o admin já editou.
            if ($config->value === null && $dados['value'] !== null) {
                $config->update(['value' => $dados['value']]);
            }
        }
    }
}
