<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\ServicoConsulta;

/**
 * Recebe o envio do pediatra pela ficha pública. Sem sessão e sem CSRF de
 * sessão: a autorização É o código de uso único (que se queima no envio,
 * gravando IP e user-agent). Tudo entra como pendente até os pais confirmarem.
 */
final class ConsultaApiController
{
    public function enviar(Requisicao $requisicao): void
    {
        $entrada = $_POST !== [] ? $_POST : $requisicao->json();
        $resultado = (new ServicoConsulta())->registrarEnvio(
            $requisicao->parametro('codigo'),
            $entrada,
            $requisicao->ip(),
            $requisicao->userAgent()
        );
        if ($resultado['erro'] !== null) {
            Resposta::erroJson($resultado['erro'], 422);
        }
        Resposta::json(['ok' => true, 'mensagem' => 'Dados enviados. A família confirma no aplicativo.']);
    }
}
