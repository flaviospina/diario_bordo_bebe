<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCriancas;
use App\Services\ServicoGrade;
use App\Services\ServicoRelatorios;
use App\Services\ServicoResumo;

final class RelatorioController
{
    public function index(Requisicao $requisicao): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Relatórios']);
        }
        $periodo = $requisicao->get('periodo') === '30' ? 30 : 7;
        $ate = hoje();
        $de = date('Y-m-d', strtotime("-" . ($periodo - 1) . " days"));

        Visao::exibir('relatorios/index', [
            'titulo' => 'Relatórios',
            'crianca' => $crianca,
            'criancas' => (new RepositorioCriancas())->listar(),
            'periodo' => $periodo,
            'dias' => (new ServicoRelatorios())->agregadosPorDia((int)$crianca['id'], $de, $ate),
        ]);
    }

    public function resumo(Requisicao $requisicao): void
    {
        $data = $requisicao->parametro('data');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            Visao::erro404();
        }
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Resumo']);
        }
        Visao::exibir('relatorios/resumo', [
            'titulo' => 'Resumo de ' . data_br($data . ' 0:0', 'd/m/Y'),
            'crianca' => $crianca,
            'data' => $data,
            'texto' => (new ServicoResumo(\App\Core\Autenticacao::familiaId()))->gerarTexto($crianca, $data),
            'dia' => $grade->montarDia($crianca, $data),
        ]);
    }

    public function pediatra(Requisicao $requisicao): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Modo Pediatra']);
        }
        $ate = hoje();
        $de = date('Y-m-d', strtotime('-29 days'));
        Visao::exibir('relatorios/pediatra', [
            'titulo' => 'Modo Pediatra',
            'crianca' => $crianca,
            'criancas' => (new RepositorioCriancas())->listar(),
            'de' => $de,
            'ate' => $ate,
            'dias' => (new ServicoRelatorios())->agregadosPorDia((int)$crianca['id'], $de, $ate),
        ]);
    }

    /** Gera exportações (CSV do período, PDF do resumo, PDF pediatra, LGPD JSON). */
    public function exportar(Requisicao $requisicao): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->post('crianca'));
        $servico = new ServicoRelatorios();
        $tipo = (string)$requisicao->post('tipo', '');

        $dataOk = static fn(?string $d, string $padrao): string =>
            is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 ? $d : $padrao;

        if ($tipo === 'lgpd_json') {
            $codigo = $servico->exportarDadosFamiliaJson();
            Resposta::redirecionarRota('download.baixar', ['codigo' => $codigo]);
        }
        if ($crianca === null) {
            Sessao::flash('erro', 'Cadastre uma criança primeiro.');
            Resposta::redirecionarRota('relatorios.index');
        }

        $codigo = match ($tipo) {
            'csv' => $servico->exportarCsv(
                $crianca,
                $dataOk($requisicao->post('de'), date('Y-m-d', strtotime('-29 days'))),
                $dataOk($requisicao->post('ate'), hoje())
            ),
            'pdf_resumo' => $servico->gerarPdfResumo($crianca, $dataOk($requisicao->post('data'), hoje())),
            'pdf_pediatra' => $servico->gerarPdfPediatra(
                $crianca,
                date('Y-m-d', strtotime('-29 days')),
                hoje()
            ),
            default => null,
        };
        if ($codigo === null) {
            Visao::erro404();
        }
        Resposta::redirecionarRota('download.baixar', ['codigo' => $codigo]);
    }
}
