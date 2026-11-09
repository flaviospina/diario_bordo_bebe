<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCardapio;
use App\Repositories\RepositorioCriancas;
use App\Services\ServicoCardapio;
use App\Services\ServicoGrade;

/**
 * Compras semanais + cardápio (Rodada 6): extensão do módulo Itens/Suprimentos.
 * Todos da família veem; cuidador e responsáveis marcam o carrinho; só os
 * responsáveis montam o cardápio, criam semanas e importam marcações.
 */
final class ComprasController
{
    public function index(Requisicao $requisicao): void
    {
        $this->exigirFamilia();
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Visao::exibir('cuidador/sem_crianca', ['titulo' => 'Compras semanais']);
        }
        $repositorio = new RepositorioCardapio();
        $semanas = $repositorio->listarSemanas((int)$crianca['id']);

        $semana = null;
        $pedida = (int)$requisicao->get('semana', '0');
        if ($pedida > 0) {
            $semana = $repositorio->buscarSemana($pedida);
            if ($semana !== null && (int)$semana['crianca_id'] !== (int)$crianca['id']) {
                $semana = null;
            }
        }
        $semana ??= $repositorio->semanaDaData((int)$crianca['id'], hoje()) ?? ($semanas[0] ?? null);

        $itens = $semana !== null ? $repositorio->itensDaSemana((int)$semana['id']) : [];
        $modelo = ServicoCardapio::modelo();
        Visao::exibir('compras/index', [
            'titulo' => 'Compras semanais',
            'crianca' => $crianca,
            'criancas' => (new RepositorioCriancas())->listar(),
            'semanas' => $semanas,
            'semana' => $semana,
            'dias' => $semana !== null ? $repositorio->diasDaSemana((int)$semana['id']) : [],
            'itens' => $itens,
            'progresso' => ServicoCardapio::progresso($itens),
            'modelo' => $modelo,
            'modeloCarregado' => $repositorio->semanaPorInicio((int)$crianca['id'], (string)$modelo['inicio']) !== null,
            'podeEditar' => Autenticacao::temPapel('responsavel', 'admin_familia'),
            'podeMarcar' => Autenticacao::temPapel('responsavel', 'admin_familia', 'cuidador'),
        ]);
    }

    public function acao(Requisicao $requisicao): void
    {
        $this->exigirFamilia();
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->post('crianca'));
        if ($crianca === null) {
            Resposta::redirecionarRota('compras');
        }
        $acao = (string)$requisicao->post('acao', '');
        $repositorio = new RepositorioCardapio();
        $servico = new ServicoCardapio();
        $podeEditar = Autenticacao::temPapel('responsavel', 'admin_familia');
        $voltar = static fn(?int $semanaId = null): never => Resposta::redirecionarCaminho(
            url('compras') . '?crianca=' . rawurlencode((string)$crianca['slug']) . ($semanaId !== null ? '&semana=' . $semanaId : '')
        );

        // ── Semanas (responsáveis) ────────────────────────────
        if ($acao === 'carregar_modelo' && $podeEditar) {
            $resultado = $servico->carregarModelo((int)$crianca['id']);
            if ($resultado['criada']) {
                $servico->avisarCuidadoras(
                    'Cardápio e lista de compras da semana publicados',
                    ServicoCardapioNome() . ' carregou a semana-modelo de 02 a 08/11: 28 itens para comprar e o cardápio de cada dia (2 frutas + almoço).',
                    url('compras')
                );
                Sessao::flash('sucesso', 'Semana-modelo carregada: 28 itens em 6 categorias e o cardápio dos 7 dias.');
            } else {
                Sessao::flash('aviso', 'A semana-modelo já estava carregada — nada foi duplicado.');
            }
            $voltar($resultado['semana_id']);
        }
        // ── Planejamento alimentar importado (Rodada 8) ──────
        if (($acao === 'planejamento_preparar' || $acao === 'planejamento_aplicar') && $podeEditar) {
            $plano = ServicoCardapio::planejamento();
            $aplicar = $acao === 'planejamento_aplicar';
            $sobrescrever = $aplicar && $requisicao->post('sobrescrever_manuais') === '1';
            try {
                $resultado = $servico->importarPlanejamento((int)$crianca['id'], $plano, $aplicar, $sobrescrever);
            } catch (\Throwable $e) {
                error_log('Importação do planejamento falhou: ' . $e->getMessage());
                Sessao::flash('erro', 'A importação falhou e nada foi gravado (transação desfeita). Tente de novo.');
                $voltar();
            }
            if (!empty($resultado['erros'])) {
                Sessao::flash('erro', 'Arquivo de planejamento inválido: ' . implode(' ', array_slice($resultado['erros'], 0, 3)));
                $voltar();
            }
            if (!$aplicar) {
                Visao::exibir('compras/planejamento', [
                    'titulo' => 'Importar planejamento',
                    'crianca' => $crianca,
                    'plano' => $plano,
                    'resumo' => $resultado,
                ]);
            }
            $alterados = $resultado['criar'] + $resultado['atualizar'] + $resultado['sobrescritos'];
            Sessao::flash('sucesso', 'Planejamento de ' . data_br($plano['inicio'] . ' 00:00:00', 'd/m') . ' a ' . data_br($plano['fim'] . ' 00:00:00', 'd/m')
                . ' aplicado: ' . $resultado['criar'] . ' refeições novas, ' . $resultado['atualizar'] . ' atualizadas, '
                . $resultado['identicos'] . ' já idênticas, ' . $resultado['conflitos'] . ' preservadas (editadas por vocês)'
                . ($resultado['sobrescritos'] > 0 ? ', ' . $resultado['sobrescritos'] . ' sobrescritas a seu pedido' : '') . '.'
                . ($alterados > 0 ? ' A cuidadora foi avisada.' : ' Nada mudou.'));
            $semanaInicio = $repositorio->semanaPorInicio((int)$crianca['id'], (string)$plano['inicio']);
            $voltar($semanaInicio !== null ? (int)$semanaInicio['id'] : null);
        }
        if ($acao === 'nova_semana' && $podeEditar) {
            $inicio = (string)$requisicao->post('inicio', '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) !== 1) {
                Sessao::flash('erro', 'Informe a data de início da semana.');
                $voltar();
            }
            $resultado = $servico->novaSemana((int)$crianca['id'], $inicio, $requisicao->post('do_modelo') === '1');
            if ($resultado['criada']) {
                $servico->avisarCuidadoras(
                    'Nova semana de cardápio criada',
                    ServicoCardapioNome() . ' criou a semana que começa em ' . data_br($inicio . ' 00:00:00', 'd/m/Y')
                    . ($requisicao->post('do_modelo') === '1' ? ' a partir do modelo (revise o cardápio).' : ' — o cardápio ainda será preenchido.'),
                    url('compras')
                );
                Sessao::flash('sucesso', 'Semana criada. ' . ($requisicao->post('do_modelo') === '1' ? 'Itens copiados do modelo, todos desmarcados — revise o cardápio.' : 'Monte o cardápio e a lista.'));
            } else {
                Sessao::flash('aviso', 'Já existe uma semana começando nessa data.');
            }
            $voltar($resultado['semana_id']);
        }

        // Tudo abaixo precisa de uma semana válida da criança
        $semana = $repositorio->buscarSemana((int)$requisicao->post('semana_id', '0'));
        if ($semana === null || (int)$semana['crianca_id'] !== (int)$crianca['id']) {
            Sessao::flash('erro', 'Semana não encontrada.');
            $voltar();
        }
        $semanaId = (int)$semana['id'];

        if ($acao === 'salvar_cardapio' && $podeEditar) {
            $mudancas = $servico->salvarCardapio($semana, (array)($_POST['dias'] ?? []));
            if ($mudancas !== []) {
                $servico->avisarCuidadoras(
                    ServicoCardapioNome() . ' atualizou o cardápio (' . data_br($semana['inicio'] . ' 00:00:00', 'd/m') . '–' . data_br($semana['fim'] . ' 00:00:00', 'd/m') . ')',
                    implode("\n", array_slice($mudancas, 0, 7)),
                    url('compras') . '?semana=' . $semanaId
                );
                Sessao::flash('sucesso', 'Cardápio salvo — a cuidadora foi avisada (app, celular e e-mail).');
            } else {
                Sessao::flash('sucesso', 'Cardápio salvo (sem mudanças).');
            }
            $voltar($semanaId);
        }
        if ($acao === 'desmarcar_todos' && Autenticacao::temPapel('responsavel', 'admin_familia', 'cuidador')) {
            $repositorio->desmarcarTodos($semanaId);
            Sessao::flash('sucesso', 'Todos os itens foram desmarcados.');
            $voltar($semanaId);
        }
        if ($acao === 'item_adicionar' && $podeEditar) {
            $nome = trim((string)$requisicao->post('nome', ''));
            $categoria = trim((string)$requisicao->post('categoria', '')) ?: 'Outros';
            if ($nome === '') {
                Sessao::flash('erro', 'Informe o nome do item.');
                $voltar($semanaId);
            }
            $stable = 'x-' . substr(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(@iconv('UTF-8', 'ASCII//TRANSLIT', $nome) ?: $nome)) ?? 'item', 0, 30) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $ordem = 1 + count(array_filter($repositorio->itensDaSemana($semanaId), static fn(array $i): bool => $i['categoria'] === $categoria));
            $repositorio->adicionarItem($semanaId, $stable, $categoria, $nome, trim((string)$requisicao->post('quantidade', '')) ?: null, trim((string)$requisicao->post('nota', '')) ?: null, $ordem);
            Sessao::flash('sucesso', 'Item adicionado à lista.');
            $voltar($semanaId);
        }
        if ($acao === 'item_remover' && $podeEditar) {
            $item = $repositorio->buscarItem((int)$requisicao->post('item_id', '0'));
            if ($item !== null && (int)$item['semana_id'] === $semanaId) {
                $repositorio->removerItem((int)$item['id']);
                Sessao::flash('sucesso', 'Item removido da lista.');
            }
            $voltar($semanaId);
        }

        // ── Importação do HTML marcado (2 passos: prévia → aplicar) ──
        if ($acao === 'importar_preparar' && $podeEditar) {
            $arquivo = $_FILES['arquivo_html'] ?? null;
            if ($arquivo === null || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                Sessao::flash('erro', 'Selecione o arquivo HTML salvo com as marcações.');
                $voltar($semanaId);
            }
            if ((int)$arquivo['size'] > ServicoCardapio::TAMANHO_MAXIMO_HTML) {
                Sessao::flash('erro', 'Arquivo grande demais (limite de 2 MB).');
                $voltar($semanaId);
            }
            $conteudo = (string)file_get_contents((string)$arquivo['tmp_name']);
            $itens = $repositorio->itensDaSemana($semanaId);
            $leitura = ServicoCardapio::lerEstadoDoHtml($conteudo, array_map(static fn(array $i): string => (string)$i['stable_id'], $itens));
            if ($leitura['erro'] !== null) {
                Sessao::flash('erro', $leitura['erro']);
                $voltar($semanaId);
            }
            $porStable = [];
            foreach ($itens as $item) {
                $porStable[(string)$item['stable_id']] = $item;
            }
            Visao::exibir('compras/importar', [
                'titulo' => 'Importar marcações',
                'crianca' => $crianca,
                'semana' => $semana,
                'marcar' => array_map(static fn(string $id): array => $porStable[$id], $leitura['marcar']),
                'desconhecidos' => $leitura['desconhecidos'],
                'atualizadoEm' => $leitura['atualizado_em'],
            ]);
        }
        if ($acao === 'importar_aplicar' && $podeEditar) {
            $conhecidos = array_map(static fn(array $i): string => (string)$i['stable_id'], $repositorio->itensDaSemana($semanaId));
            $aplicados = 0;
            foreach (array_unique(array_map('strval', (array)($_POST['ids'] ?? []))) as $stable) {
                if (in_array($stable, $conhecidos, true)) {
                    $aplicados += $repositorio->marcarPorStableId($semanaId, $stable, true, Autenticacao::id());
                }
            }
            Sessao::flash('sucesso', $aplicados . ' item(ns) marcado(s) a partir do HTML. Itens já marcados não foram alterados.');
            $voltar($semanaId);
        }

        Sessao::flash('erro', 'Ação não permitida.');
        $voltar($semanaId);
    }

    /** POST /api/compras/marcar  {item_id, comprado} → {ok, progresso} */
    public function marcar(Requisicao $requisicao): void
    {
        if (!Autenticacao::temPapel('responsavel', 'admin_familia', 'cuidador')) {
            Resposta::erroJson('Sem permissão para marcar itens.', 403);
        }
        $dados = $requisicao->json();
        $repositorio = new RepositorioCardapio();
        $item = $repositorio->buscarItem((int)($dados['item_id'] ?? 0));
        if ($item === null) {
            Resposta::erroJson('Item não encontrado.', 404);
        }
        $repositorio->marcarItem((int)$item['id'], !empty($dados['comprado']), Autenticacao::id());
        $progresso = ServicoCardapio::progresso($repositorio->itensDaSemana((int)$item['semana_id']));
        Resposta::json([
            'ok' => true,
            'item_id' => (int)$item['id'],
            'comprado' => !empty($dados['comprado']),
            'total' => $progresso['total'],
            'comprados' => $progresso['comprados'],
            'porcentagem' => $progresso['porcentagem'],
            'categorias' => array_map(static fn(array $c): array => ['total' => $c['total'], 'comprados' => $c['comprados']], $progresso['categorias']),
        ]);
    }

    private function exigirFamilia(): void
    {
        if (Autenticacao::papel() === 'super_admin' || Autenticacao::familiaId() <= 0) {
            Visao::erro403();
        }
    }
}

/** Primeiro nome de quem está agindo (atalho para os avisos). */
function ServicoCardapioNome(): string
{
    return \App\Services\ServicoAvisos::nomeDoAutor();
}
