<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioRoteiro;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoGrade;

/**
 * Painel de roteiro: os pais montam os blocos prescritos do dia.
 * Opcional por configuração (roteiro_ativo) — nunca obrigatório.
 */
final class RoteiroController
{
    private const DIAS = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];

    public function editar(Requisicao $requisicao): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Sessao::flash('erro', 'Cadastre uma criança antes de montar o roteiro.');
            Resposta::redirecionarRota('config.criancas');
        }
        $config = new ServicoConfiguracoes();
        Visao::exibir('roteiro/editar', [
            'titulo' => 'Roteiro do dia',
            'crianca' => $crianca,
            'criancas' => (new RepositorioCriancas())->listar(),
            'blocos' => (new RepositorioRoteiro())->listar((int)$crianca['id']),
            'categorias' => (new RepositorioCategorias())->ativasParaFamilia(
                (array)$config->obter('categorias_inativas')
            ),
            'roteiroAtivo' => (bool)$config->obter('roteiro_ativo'),
        ]);
    }

    public function salvar(Requisicao $requisicao): void
    {
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->post('crianca'));
        if ($crianca === null) {
            Resposta::redirecionarRota('roteiro.editar');
        }
        $repositorio = new RepositorioRoteiro();
        $acao = (string)$requisicao->post('acao', '');

        if ($acao === 'remover') {
            $bloco = $repositorio->buscar((int)$requisicao->post('bloco_id', '0'));
            if ($bloco !== null && (int)$bloco['crianca_id'] === (int)$crianca['id']) {
                $repositorio->remover((int)$bloco['id']);
                Sessao::flash('sucesso', 'Bloco removido do roteiro.');
            }
            Resposta::redirecionarRota('roteiro.editar');
        }

        // criar / atualizar
        $dias = array_values(array_intersect((array)($_POST['dias'] ?? []), self::DIAS));
        $titulo = trim((string)$requisicao->post('titulo', ''));
        $horaInicio = (string)$requisicao->post('hora_inicio', '');
        $horaFim = (string)$requisicao->post('hora_fim', '');
        $horaOk = static fn(string $h): bool => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h) === 1;

        if ($titulo === '' || $dias === [] || !$horaOk($horaInicio) || !$horaOk($horaFim) || $horaFim <= $horaInicio) {
            Sessao::flash('erro', 'Preencha título, dias da semana e um intervalo de horário válido.');
            Resposta::redirecionarRota('roteiro.editar');
        }

        $categoriaId = null;
        $slugCategoria = (string)$requisicao->post('categoria', '');
        if ($slugCategoria !== '') {
            $categoria = (new RepositorioCategorias())->buscarPorSlug($slugCategoria);
            $categoriaId = $categoria !== null ? (int)$categoria['id'] : null;
        }

        $bloco = [
            'crianca_id' => (int)$crianca['id'],
            'dias_semana' => implode(',', $dias),
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'titulo' => mb_substr($titulo, 0, 120),
            'categoria_id' => $categoriaId,
            'instrucao' => trim((string)$requisicao->post('instrucao', '')) ?: null,
            'obrigatorio' => $requisicao->post('obrigatorio') === '1' ? 1 : 0,
        ];

        $blocoId = (int)$requisicao->post('bloco_id', '0');
        if ($acao === 'atualizar' && $blocoId > 0) {
            $existente = $repositorio->buscar($blocoId);
            if ($existente !== null && (int)$existente['crianca_id'] === (int)$crianca['id']) {
                $repositorio->atualizar($blocoId, $bloco);
                Sessao::flash('sucesso', 'Bloco atualizado.');
            }
        } else {
            $repositorio->criar($bloco);
            Sessao::flash('sucesso', 'Bloco adicionado ao roteiro.');
        }
        Resposta::redirecionarRota('roteiro.editar');
    }
}
