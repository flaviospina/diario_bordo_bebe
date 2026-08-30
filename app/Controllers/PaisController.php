<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Visao;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioSolicitacoes;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoGrade;

/**
 * Tela dos pais — "Acompanhar": a mesma grade, em modo leitura,
 * com faixa de status, semáforo de omissão e badges.
 */
final class PaisController
{
    public function acompanhar(Requisicao $requisicao): void
    {
        $this->renderizar($requisicao, hoje());
    }

    public function acompanharData(Requisicao $requisicao): void
    {
        $data = $requisicao->parametro('data');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            Visao::erro404();
        }
        $this->renderizar($requisicao, $data);
    }

    private function renderizar(Requisicao $requisicao, string $data): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Acompanhar']);
        }
        $config = new ServicoConfiguracoes();
        $alerta = $config->obter('alerta_omissao');
        $dia = $grade->montarDia($crianca, $data);

        // Semáforo de omissão: verde/âmbar/vermelho conforme o silêncio atual
        $semaforo = 'verde';
        $silencioMinutos = null;
        if ($data === hoje()) {
            $janela = $dia['janela'];
            $agora = date('H:i');
            if ($agora >= $janela['inicio'] && $agora <= $janela['fim']) {
                $referencia = max(
                    strtotime($data . ' ' . $janela['inicio']),
                    $dia['ultima_atividade'] !== null ? strtotime($dia['ultima_atividade']) : 0
                );
                $silencioMinutos = (int)((time() - $referencia) / 60);
                $limite = max(15, (int)($alerta['minutos'] ?? 90));
                $semaforo = $silencioMinutos >= $limite ? 'vermelho'
                    : ($silencioMinutos >= (int)($limite * 0.6) ? 'ambar' : 'verde');
            }
        }

        Visao::exibir('pais/acompanhar', [
            'titulo' => 'Acompanhar',
            'crianca' => $crianca,
            'criancas' => (new RepositorioCriancas())->listar(),
            'dia' => $dia,
            'ehHoje' => $data === hoje(),
            'semaforo' => $semaforo,
            'silencioMinutos' => $silencioMinutos,
            'alertaAtivo' => !empty($alerta['ativo']),
            'solicitacoesPendentes' => (new RepositorioSolicitacoes())->contarPendentes(),
            'intercorrenciasSemCiencia' => (new RepositorioIntercorrencias())->contarSemCiencia(),
            'intercorrencias' => array_values(array_filter(
                (new RepositorioIntercorrencias())->listar(10),
                static fn(array $i): bool => substr((string)$i['ocorrido_em'], 0, 10) === $data
            )),
        ]);
    }
}
