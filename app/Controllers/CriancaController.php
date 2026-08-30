<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioRegistros;
use App\Services\ServicoConfiguracoes;

final class CriancaController
{
    public function ver(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        Visao::exibir('crianca/ver', [
            'titulo' => $crianca['nome'],
            'crianca' => $crianca,
        ]);
    }

    public function timeline(Requisicao $requisicao): void
    {
        $crianca = $this->criancaDaRota($requisicao);
        $categoriaSlug = $requisicao->get('categoria');
        $de = $requisicao->get('de');
        $ate = $requisicao->get('ate');
        $dataOk = static fn(?string $d): ?string =>
            is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1 ? $d : null;

        $registros = (new RepositorioRegistros())->linhaDoTempo(
            (int)$crianca['id'],
            $categoriaSlug,
            $dataOk($de),
            $dataOk($ate),
            200
        );
        Visao::exibir('crianca/timeline', [
            'titulo' => 'Linha do tempo — ' . ($crianca['apelido'] ?: $crianca['nome']),
            'crianca' => $crianca,
            'registros' => $registros,
            'categorias' => (new RepositorioCategorias())->ativasParaFamilia(
                (array)(new ServicoConfiguracoes())->obter('categorias_inativas')
            ),
            'filtros' => ['categoria' => (string)$categoriaSlug, 'de' => (string)$de, 'ate' => (string)$ate],
        ]);
    }

    private function criancaDaRota(Requisicao $requisicao): array
    {
        $crianca = (new RepositorioCriancas())->buscarPorSlug($requisicao->parametro('slug'));
        if ($crianca === null) {
            Visao::erro404();
        }
        return $crianca;
    }
}
