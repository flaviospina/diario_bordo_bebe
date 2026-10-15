<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioLogAcessos;

/**
 * Intercorrências com fluxo de CIÊNCIA formal dos pais (regra 8.6).
 */
final class IntercorrenciaController
{
    public function ver(Requisicao $requisicao): void
    {
        $intercorrencia = $this->daRota($requisicao);
        Visao::exibir('intercorrencia/ver', [
            'titulo' => 'Intercorrência',
            'intercorrencia' => $intercorrencia,
            'podeDarCiencia' => $intercorrencia['ciencia_em'] === null
                && Autenticacao::temPapel('responsavel', 'admin_familia'),
        ]);
    }

    public function darCiencia(Requisicao $requisicao): void
    {
        $intercorrencia = $this->daRota($requisicao);
        if (!Autenticacao::temPapel('responsavel', 'admin_familia')) {
            Visao::erro403();
        }
        if ($intercorrencia['ciencia_em'] === null) {
            (new RepositorioIntercorrencias())->registrarCiencia((int)$intercorrencia['id'], Autenticacao::id());
            (new \App\Services\ServicoAvisos())->avisarUsuario(
                (int)$intercorrencia['usuario_id'],
                'intercorrencia_ciencia',
                \App\Services\ServicoAvisos::nomeDoAutor() . ' tomou ciência da intercorrência',
                mb_substr((string)$intercorrencia['descricao'], 0, 160),
                url('intercorrencia.ver', ['codigo' => (string)$intercorrencia['codigo_publico']])
            );
            (new RepositorioLogAcessos())->registrar(
                Autenticacao::familiaId(),
                Autenticacao::id(),
                'intercorrencia_ciencia',
                'intercorrencias',
                (int)$intercorrencia['id'],
                $requisicao->ip()
            );
            Sessao::flash('sucesso', 'Ciência registrada.');
        }
        Resposta::redirecionarRota('intercorrencia.ver', ['codigo' => $intercorrencia['codigo_publico']]);
    }

    private function daRota(Requisicao $requisicao): array
    {
        $intercorrencia = (new RepositorioIntercorrencias())->buscarPorCodigo($requisicao->parametro('codigo'));
        if ($intercorrencia === null) {
            Visao::erro404();
        }
        return $intercorrencia;
    }
}
