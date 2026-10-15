<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\WebPush;
use App\Repositories\RepositorioConfiguracoesPlataforma;
use App\Repositories\RepositorioPushInscricoes;

/**
 * Notificações push (Web Push/VAPID) para os aparelhos inscritos.
 * As chaves VAPID nascem sozinhas na primeira vez e ficam guardadas em
 * configuracoes_plataforma — nada a configurar no .env.
 */
final class ServicoPush
{
    private const CHAVE_PUBLICA = 'vapid_publica';
    private const CHAVE_PRIVADA = 'vapid_privada_pem';

    /** Chave pública (base64url) que o navegador usa para se inscrever. */
    public static function chavePublica(): string
    {
        $config = new RepositorioConfiguracoesPlataforma();
        $publica = $config->obter(self::CHAVE_PUBLICA);
        $privada = $config->obter(self::CHAVE_PRIVADA);
        if ($publica === '' || $privada === '') {
            [$publica, $privada] = WebPush::gerarChavesVapid();
            $config->salvar(self::CHAVE_PUBLICA, $publica);
            $config->salvar(self::CHAVE_PRIVADA, $privada);
        }
        return $publica;
    }

    /**
     * Envia a notificação para todos os aparelhos dos usuários.
     * Inscrições mortas (404/410) são apagadas; falhas repetidas também.
     * @param int[] $usuarioIds
     * @return array{enviados:int, falhas:int}
     */
    public function enviarParaUsuarios(array $usuarioIds, string $titulo, string $mensagem, ?string $url, string $tag = 'diario'): array
    {
        $inscricoes = new RepositorioPushInscricoes();
        $lista = $inscricoes->dosUsuarios($usuarioIds);
        if ($lista === []) {
            return ['enviados' => 0, 'falhas' => 0];
        }

        $publica = self::chavePublica();
        $privada = (new RepositorioConfiguracoesPlataforma())->obter(self::CHAVE_PRIVADA);
        $remetente = Ambiente::obter('EMAIL_REMETENTE', 'no-reply@itthrive.com.br');
        $push = new WebPush($publica, $privada, 'mailto:' . $remetente);
        $payload = json_encode([
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => $url,
            'tag' => $tag,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $enviados = 0;
        $falhas = 0;
        foreach ($lista as $inscricao) {
            try {
                $status = $push->enviar(
                    (string)$inscricao['endpoint'],
                    (string)$inscricao['chave_p256dh'],
                    (string)$inscricao['chave_auth'],
                    $payload
                );
            } catch (\Throwable $excecao) {
                error_log('Push falhou: ' . $excecao->getMessage());
                $status = 0;
            }
            if ($status >= 200 && $status < 300) {
                $inscricoes->registrarResultado((int)$inscricao['id'], true);
                $enviados++;
            } elseif ($status === 404 || $status === 410) {
                $inscricoes->remover((int)$inscricao['id']); // aparelho desinscreveu
                $falhas++;
            } else {
                $inscricoes->registrarResultado((int)$inscricao['id'], false);
                if ((int)$inscricao['falhas'] + 1 >= 5) {
                    $inscricoes->remover((int)$inscricao['id']);
                }
                $falhas++;
            }
        }
        return ['enviados' => $enviados, 'falhas' => $falhas];
    }
}
