<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Visao;
use App\Repositories\RepositorioVersoes;

/**
 * Auditoria da família: versões de registros, edições, aprovações e acessos.
 * Visível apenas ao admin_familia.
 */
final class AuditoriaController
{
    public function index(Requisicao $requisicao): void
    {
        $bd = \App\Core\BancoDados::conexao();
        $declaracao = $bd->prepare(
            'SELECT l.*, u.nome AS usuario_nome
               FROM log_acessos l LEFT JOIN usuarios u ON u.id = l.usuario_id
              WHERE l.familia_id = :familia
              ORDER BY l.criado_em DESC, l.id DESC LIMIT 150'
        );
        $declaracao->execute(['familia' => Autenticacao::familiaId()]);

        Visao::exibir('auditoria/index', [
            'titulo' => 'Auditoria',
            'versoes' => (new RepositorioVersoes())->listarDaFamilia(Autenticacao::familiaId(), 100),
            'acessos' => $declaracao->fetchAll(),
        ]);
    }
}
