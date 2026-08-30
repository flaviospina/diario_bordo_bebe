<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioCriancas;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoGrade;

/**
 * Tela principal do cuidador — "Meu Dia" (mobile-first).
 */
final class CuidadorController
{
    public function dia(Requisicao $requisicao): void
    {
        $this->renderizarDia($requisicao, hoje());
    }

    public function diaData(Requisicao $requisicao): void
    {
        $data = $requisicao->parametro('data');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            Visao::erro404();
        }
        $this->renderizarDia($requisicao, $data);
    }

    private function renderizarDia(Requisicao $requisicao, string $data): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        $criancas = (new RepositorioCriancas())->listar();
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Meu Dia']);
        }

        $config = new ServicoConfiguracoes();
        $inativas = (array)$config->obter('categorias_inativas');
        $categorias = (new RepositorioCategorias())->ativasParaFamilia($inativas);
        $rapidas = array_values(array_filter(
            $categorias,
            static fn(array $c): bool => in_array($c['slug'], (array)$config->obter('acoes_rapidas'), true)
        ));

        Visao::exibir('cuidador/dia', [
            'titulo' => 'Meu Dia',
            'crianca' => $crianca,
            'criancas' => $criancas,
            'dia' => $grade->montarDia($crianca, $data),
            'categorias' => $categorias,
            'rapidas' => $rapidas,
            'ehHoje' => $data === hoje(),
        ]);
    }
}
