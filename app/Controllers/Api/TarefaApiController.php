<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Ambiente;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\ServicoTarefas;

/**
 * Endpoint das tarefas agendadas (cron do cPanel ou scheduler do n8n):
 *   POST /api/tarefas/{tarefa}   com header X-Token: TAREFAS_TOKEN
 * Tarefas: fila | omissao | resumo | expurgo | acompanhamento. Todas idempotentes.
 *
 * Sugestão de cron (a cada 5 min):
 *   curl -s -X POST -H "X-Token: SEU_TOKEN" https://.../diariobebe/api/tarefas/fila
 *   curl -s -X POST -H "X-Token: SEU_TOKEN" https://.../diariobebe/api/tarefas/omissao
 *   curl -s -X POST -H "X-Token: SEU_TOKEN" https://.../diariobebe/api/tarefas/resumo
 * E uma vez por dia:
 *   curl -s -X POST -H "X-Token: SEU_TOKEN" https://.../diariobebe/api/tarefas/expurgo
 *   curl -s -X POST -H "X-Token: SEU_TOKEN" https://.../diariobebe/api/tarefas/acompanhamento
 * (o acompanhamento gera no máximo UMA análise por criança por semana,
 *  então rodar todo dia é seguro — os dias sem nada a fazer não custam nada)
 */
final class TarefaApiController
{
    public function executar(Requisicao $requisicao): void
    {
        $tokenEsperado = Ambiente::obter('TAREFAS_TOKEN');
        $tokenRecebido = $requisicao->cabecalho('X-Token');
        if ($tokenRecebido === '') {
            $tokenRecebido = (string)$requisicao->post('token', '');
        }
        if ($tokenEsperado === '' || strlen($tokenEsperado) < 20
            || !hash_equals($tokenEsperado, $tokenRecebido)) {
            Resposta::erroJson('Token inválido.', 403);
        }

        $servico = new ServicoTarefas();
        $resultado = match ($requisicao->parametro('tarefa')) {
            'fila'           => $servico->processarFila(),
            'omissao'        => $servico->verificarOmissao(),
            'resumo'         => $servico->gerarResumos(),
            'expurgo'        => $servico->expurgarRetencao(),
            'acompanhamento' => $servico->gerarAcompanhamentos(),
            default          => null,
        };
        if ($resultado === null) {
            Resposta::erroJson('Tarefa desconhecida. Use: fila, omissao, resumo, expurgo ou acompanhamento.', 404);
        }
        Resposta::json(['tarefa' => $requisicao->parametro('tarefa'), 'ok' => true] + $resultado);
    }
}
