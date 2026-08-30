<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioTurnos;

/**
 * Turnos do cuidador: abertos automaticamente no primeiro registro do dia,
 * com ajuste manual permitido e auditado (regra 8.8).
 */
final class TurnoController
{
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('turnos/index', [
            'titulo' => 'Turnos',
            'turnos' => (new RepositorioTurnos())->listar(),
            'podeAjustar' => Autenticacao::temPapel('responsavel', 'admin_familia', 'cuidador'),
        ]);
    }

    public function ajustar(Requisicao $requisicao): void
    {
        $turnos = new RepositorioTurnos();
        $turno = $turnos->buscar((int)$requisicao->post('turno_id', '0'));
        if ($turno === null) {
            Visao::erro404();
        }
        // Cuidador só ajusta o próprio turno; responsáveis ajustam qualquer um
        if (Autenticacao::papel() === 'cuidador' && (int)$turno['usuario_id'] !== Autenticacao::id()) {
            Visao::erro403();
        }

        $valido = static fn(?string $v): bool => is_string($v)
            && preg_match('/^\d{4}-\d{2}-\d{2}T[0-2]\d:[0-5]\d$/', $v) === 1;
        $entrada = (string)$requisicao->post('entrada', '');
        $saida = (string)$requisicao->post('saida', '');
        if (!$valido($entrada)) {
            Sessao::flash('erro', 'Informe a entrada no formato correto.');
            Resposta::redirecionarRota('turnos.index');
        }
        $entradaSql = str_replace('T', ' ', $entrada) . ':00';
        $saidaSql = $valido($saida) ? str_replace('T', ' ', $saida) . ':00' : null;
        if ($saidaSql !== null && $saidaSql <= $entradaSql) {
            Sessao::flash('erro', 'A saída precisa ser depois da entrada.');
            Resposta::redirecionarRota('turnos.index');
        }

        $turnos->ajustar((int)$turno['id'], $entradaSql, $saidaSql, trim((string)$requisicao->post('observacao', '')) ?: null);
        (new RepositorioLogAcessos())->registrar(
            Autenticacao::familiaId(),
            Autenticacao::id(),
            'turno_ajustado',
            'turnos',
            (int)$turno['id'],
            $requisicao->ip()
        );
        Sessao::flash('sucesso', 'Turno ajustado (o ajuste fica auditado).');
        Resposta::redirecionarRota('turnos.index');
    }
}
