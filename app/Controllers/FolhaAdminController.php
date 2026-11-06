<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioFolha;
use App\Services\ServicoFolha;

/**
 * Portal da Colaboradora — Gestão (empregador / responsável autorizado).
 * Todas as ações verificam ServicoFolha::podeGerenciar() no servidor.
 */
final class FolhaAdminController
{
    private ServicoFolha $servico;
    private RepositorioFolha $repo;

    public function __construct()
    {
        $this->servico = new ServicoFolha();
        $this->repo = $this->servico->repositorio();
    }

    public function index(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $colabs = $this->repo->colaboradoras();
        $colab = $this->colaboradoraSelecionada($requisicao, $colabs);
        $aba = (string)$requisicao->get('aba', 'visao');
        if (!in_array($aba, ['visao', 'documentos', 'pagamentos', 'beneficios', 'ferias', 'eventos', 'colaboradoras'], true)) {
            $aba = 'visao';
        }
        $dados = [
            'titulo' => 'Gestão da Colaboradora',
            'portal' => 'gestao',
            'aba' => $aba,
            'colaboradoras' => $colabs,
            'colaboradora' => $colab,
            'semVinculo' => $this->repo->cuidadoresSemVinculo(),
            'gestores' => $this->repo->gestoresAutorizados(),
            'responsaveis' => $this->repo->responsaveisDaFamilia(),
            'ehAdminFamilia' => Autenticacao::papel() === 'admin_familia',
        ];
        if ($colab !== null) {
            $id = (int)$colab['id'];
            $dados['contagens'] = $this->repo->contagens($id);
            $dados['ultimoAcesso'] = $this->repo->ultimoAcesso($id);
            $filtros = ['colaboradora_id' => $id];
            $tipo = (string)$requisicao->get('tipo', '');
            if (isset(ServicoFolha::TIPOS[$tipo])) {
                $filtros['tipo'] = $tipo;
            }
            $status = (string)$requisicao->get('status', '');
            if (isset(ServicoFolha::STATUS[$status])) {
                $filtros['status'] = $status;
            }
            $dados['filtroTipo'] = $filtros['tipo'] ?? '';
            $dados['filtroStatus'] = $filtros['status'] ?? '';
            $dados['documentos'] = $this->repo->documentos($filtros);
            $dados['vales'] = $this->repo->valesTransporte($id);
            $dados['eventos'] = $this->repo->eventosRecentes(200, $id);
            $dados['contracheques'] = $this->repo->documentos(['colaboradora_id' => $id, 'tipo' => 'contracheque']);
            $dados['pagamentos'] = $this->pagamentosRecentes($dados['contracheques']);
            $dados['vale'] = null;
            $editarVt = (int)$requisicao->get('vt', '0');
            if ($editarVt > 0) {
                $dados['vale'] = $this->repo->valeTransporte($editarVt);
            }
        }
        Visao::exibir('folha/gestao/index', $dados);
    }

    public function acaoColaboradoras(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $acao = (string)$requisicao->post('acao', '');
        $voltar = static fn(string $aba = 'colaboradoras'): never => Resposta::redirecionarCaminho(url('portal.gestao') . '?aba=' . $aba);
        if ($acao === 'vincular') {
            $usuarioId = (int)$requisicao->post('usuario_id', '0');
            $admissao = ServicoFolha::dataValida($requisicao->post('admissao'));
            $obs = trim((string)$requisicao->post('observacoes', '')) ?: null;
            try {
                $id = $this->repo->vincularColaboradora($usuarioId, $admissao, $obs, Autenticacao::id());
                Sessao::flash($id > 0 ? 'sucesso' : 'erro', $id > 0 ? 'Colaboradora vinculada.' : 'Selecione uma cuidadora da sua família.');
            } catch (\Throwable) {
                Sessao::flash('erro', 'Essa pessoa já está vinculada.');
            }
            $voltar();
        }
        if ($acao === 'atualizar') {
            $colab = $this->repo->colaboradora((int)$requisicao->post('colaboradora_id', '0'));
            if ($colab !== null) {
                $situacao = in_array($requisicao->post('situacao'), ['ativa', 'encerrada'], true) ? (string)$requisicao->post('situacao') : 'ativa';
                $this->repo->atualizarColaboradora((int)$colab['id'], ServicoFolha::dataValida($requisicao->post('admissao')), $situacao, trim((string)$requisicao->post('observacoes', '')) ?: null);
                Sessao::flash('sucesso', 'Dados do vínculo atualizados.');
            }
            $voltar();
        }
        if (($acao === 'conceder' || $acao === 'retirar') && Autenticacao::papel() === 'admin_familia') {
            $usuarioId = (int)$requisicao->post('usuario_id', '0');
            if ($acao === 'conceder') {
                $this->repo->concederGestao($usuarioId, Autenticacao::id());
                Sessao::flash('sucesso', 'Acesso de gestão concedido.');
            } else {
                $this->repo->revogarGestao($usuarioId);
                Sessao::flash('sucesso', 'Acesso de gestão retirado.');
            }
            $voltar();
        }
        Sessao::flash('erro', 'Ação não permitida.');
        $voltar();
    }

    public function novoForm(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $colabs = $this->repo->colaboradoras();
        if ($colabs === []) {
            Sessao::flash('aviso', 'Vincule a colaboradora antes de importar documentos.');
            Resposta::redirecionarCaminho(url('portal.gestao') . '?aba=colaboradoras');
        }
        $colab = $this->colaboradoraSelecionada($requisicao, $colabs);
        $tipo = (string)$requisicao->get('tipo', 'contracheque');
        Visao::exibir('folha/gestao/novo', [
            'titulo' => 'Importar documento',
            'portal' => 'gestao',
            'colaboradoras' => $colabs,
            'colaboradora' => $colab,
            'tipo' => isset(ServicoFolha::TIPOS[$tipo]) ? $tipo : 'contracheque',
            'competencia' => (string)$requisicao->get('competencia', date('Y-m', strtotime('first day of last month'))),
            'limiteMb' => (int)round(ServicoFolha::limiteBytes() / 1048576),
        ]);
    }

    public function novoSalvar(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $colab = $this->repo->colaboradora((int)$requisicao->post('colaboradora_id', '0'));
        if ($colab === null) {
            Sessao::flash('erro', 'Colaboradora inválida.');
            Resposta::redirecionarRota('portal.gestao.novo');
        }
        $tipo = (string)$requisicao->post('tipo', '');
        $competencia = (string)$requisicao->post('competencia', '');
        $mes = $tipo === 'vale_transporte' ? ((string)$requisicao->post('mes_referencia', '') ?: null) : null;
        $resultado = $this->servico->criarDocumento(
            (int)$colab['id'], $tipo, $competencia,
            trim((string)$requisicao->post('descricao', '')) ?: null,
            ServicoFolha::dataValida($requisicao->post('data_pagamento')),
            $mes, $_FILES['arquivo'] ?? null
        );
        if ($resultado['erro'] !== null) {
            Sessao::flash('erro', $resultado['erro']);
            Resposta::redirecionarCaminho(url('portal.gestao.novo') . '?' . http_build_query(['colaboradora' => $colab['codigo_publico'], 'tipo' => $tipo, 'competencia' => $competencia]));
        }
        Sessao::flash('sucesso', 'PDF recebido e guardado em área privada. Confira os dados e publique quando estiver tudo certo.');
        if (!empty($resultado['aviso'])) {
            Sessao::flash('aviso', $resultado['aviso']);
        }
        if ($resultado['sugestao'] !== []) {
            Sessao::flash('aviso', 'Alguns valores foram lidos do PDF como sugestão — confira cada um antes de salvar.');
        }
        Resposta::redirecionarRota('portal.gestao.documento', ['codigo' => $resultado['documento']['codigo_publico']]);
    }

    public function documento(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $documento = $this->documentoDaRota($requisicao);
        $versaoAtual = (int)($documento['versao_atual_id'] ?? 0);
        $resumo = $versaoAtual > 0 ? $this->repo->resumoDaVersao($versaoAtual) : null;
        Visao::exibir('folha/gestao/documento', [
            'titulo' => (ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento') . ' · ' . ServicoFolha::rotuloCompetencia($documento['competencia'], true),
            'portal' => 'gestao',
            'documento' => $documento,
            'resumo' => $resumo,
            'versoes' => $this->repo->versoes((int)$documento['id']),
            'eventos' => $this->repo->eventosDoDocumento((int)$documento['id']),
            'indicadores' => $this->repo->indicadoresDeAcesso((int)$documento['id']),
            'camposResumo' => RepositorioFolha::CAMPOS_RESUMO,
            'limiteMb' => (int)round(ServicoFolha::limiteBytes() / 1048576),
        ]);
    }

    public function acaoDocumento(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $documento = $this->documentoDaRota($requisicao);
        $acao = (string)$requisicao->post('acao', '');
        $voltar = static fn(): never => Resposta::redirecionarRota('portal.gestao.documento', ['codigo' => $documento['codigo_publico']]);

        if ($acao === 'metadados') {
            if ($documento['status'] === 'revogado') {
                Sessao::flash('erro', 'Documento revogado não é editável.');
                $voltar();
            }
            $competencia = (string)$requisicao->post('competencia', '');
            if (!ServicoFolha::competenciaValida($competencia)) {
                Sessao::flash('erro', 'Competência inválida.');
                $voltar();
            }
            $mes = $documento['tipo'] === 'vale_transporte' ? ((string)$requisicao->post('mes_referencia', '') ?: null) : null;
            if ($mes !== null && !ServicoFolha::competenciaValida($mes)) {
                Sessao::flash('erro', 'Mês de referência inválido.');
                $voltar();
            }
            $equivalente = $this->repo->documentoEquivalente((int)$documento['colaboradora_id'], (string)$documento['tipo'], $competencia, $mes, (int)$documento['id']);
            if ($equivalente !== null) {
                Sessao::flash('erro', 'Já existe outro documento deste tipo nessa competência.');
                $voltar();
            }
            $this->repo->atualizarMetadados((int)$documento['id'], $competencia, trim((string)$requisicao->post('descricao', '')) ?: null,
                ServicoFolha::dataValida($requisicao->post('data_pagamento')), $mes);
            Sessao::flash('sucesso', 'Dados do documento salvos.');
            $voltar();
        }

        if ($acao === 'resumo') {
            if ($documento['status'] === 'revogado' || empty($documento['versao_atual_id'])) {
                Sessao::flash('erro', 'Não é possível salvar valores neste documento.');
                $voltar();
            }
            $valores = [];
            $invalidos = [];
            foreach (RepositorioFolha::CAMPOS_RESUMO as $campo) {
                $bruto = trim((string)$requisicao->post($campo, ''));
                $valores[$campo] = ServicoFolha::numeroBr($bruto);
                if ($bruto !== '' && $valores[$campo] === null) {
                    $invalidos[] = $campo;
                }
            }
            if ($invalidos !== []) {
                Sessao::flash('erro', 'Valor inválido em: ' . implode(', ', $invalidos) . '. Use o formato 1.234,56.');
                $voltar();
            }
            $valores['data_pagamento'] = ServicoFolha::dataValida($requisicao->post('data_pagamento'));
            $valores['observacoes'] = trim((string)$requisicao->post('observacoes', '')) ?: null;
            $anterior = $this->repo->resumoDaVersao((int)$documento['versao_atual_id']);
            $valores['origem'] = ($anterior !== null && $anterior['origem'] === 'extraido') ? 'misto' : 'manual';
            $conferido = $requisicao->post('conferido') === '1';
            $this->repo->salvarResumo((int)$documento['id'], (int)$documento['versao_atual_id'], (string)$documento['competencia'], $valores, $conferido, Autenticacao::id());
            Sessao::flash('sucesso', $conferido ? 'Valores conferidos e salvos.' : 'Valores salvos (ainda não conferidos).');
            $voltar();
        }

        if ($acao === 'publicar') {
            $erro = $this->servico->publicar($documento, $requisicao->ip());
            Sessao::flash($erro === null ? 'sucesso' : 'erro', $erro ?? 'Documento publicado — a colaboradora foi avisada (app, celular e e-mail).');
            $voltar();
        }
        if ($acao === 'substituir') {
            $erro = $this->servico->substituir($documento, $_FILES['arquivo'] ?? null, trim((string)$requisicao->post('motivo', '')) ?: null, $requisicao->ip());
            Sessao::flash($erro === null ? 'sucesso' : 'erro', $erro ?? 'Nova versão guardada. A anterior ficou no histórico e deixou de ser acessível. Reconfira os valores.');
            $voltar();
        }
        if ($acao === 'revogar') {
            $erro = $this->servico->revogar($documento, trim((string)$requisicao->post('motivo', '')) ?: null, $requisicao->ip());
            Sessao::flash($erro === null ? 'sucesso' : 'erro', $erro ?? 'Documento revogado: a colaboradora não o acessa mais; o histórico foi preservado.');
            $voltar();
        }
        if ($acao === 'excluir_rascunho') {
            $erro = $this->servico->excluirRascunho($documento);
            if ($erro !== null) {
                Sessao::flash('erro', $erro);
                $voltar();
            }
            Sessao::flash('sucesso', 'Rascunho excluído.');
            Resposta::redirecionarCaminho(url('portal.gestao') . '?aba=documentos');
        }
        Sessao::flash('erro', 'Ação não permitida.');
        $voltar();
    }

    /** Prévia do PDF para o gestor (qualquer versão). Não gera evento de consulta da colaboradora. */
    public function arquivo(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $documento = $this->documentoDaRota($requisicao);
        $versaoId = (int)($requisicao->get('versao') ?? 0) ?: (int)($documento['versao_atual_id'] ?? 0);
        $versao = $versaoId > 0 ? $this->repo->versao($versaoId) : null;
        if ($versao === null || (int)$versao['documento_id'] !== (int)$documento['id']) {
            Visao::erro404();
        }
        $caminho = $this->servico->caminhoAbsoluto($versao);
        if (!is_file($caminho)) {
            Visao::erro404();
        }
        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string)filesize($caminho));
        header('Content-Disposition: ' . ($requisicao->get('baixar') === '1' ? 'attachment' : 'inline') . '; filename="' . ServicoFolha::nomeParaDownload($documento + ['versao_numero' => $versao['numero']]) . '"');
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        readfile($caminho);
        exit;
    }

    public function valeTransporteSalvar(Requisicao $requisicao): void
    {
        $this->exigirGestao();
        $colab = $this->repo->colaboradora((int)$requisicao->post('colaboradora_id', '0'));
        $voltar = static fn(string $codigo): never => Resposta::redirecionarCaminho(url('portal.gestao') . '?aba=beneficios&colaboradora=' . $codigo);
        if ($colab === null) {
            Sessao::flash('erro', 'Colaboradora inválida.');
            Resposta::redirecionarCaminho(url('portal.gestao') . '?aba=beneficios');
        }
        if ($requisicao->post('acao') === 'excluir') {
            $vale = $this->repo->valeTransporte((int)$requisicao->post('id', '0'));
            if ($vale !== null && (int)$vale['colaboradora_id'] === (int)$colab['id']) {
                $this->repo->excluirValeTransporte((int)$vale['id']);
                Sessao::flash('sucesso', 'Lançamento de vale-transporte excluído.');
            }
            $voltar((string)$colab['codigo_publico']);
        }
        $mes = (string)$requisicao->post('mes_referencia', '');
        if (!ServicoFolha::competenciaValida($mes)) {
            Sessao::flash('erro', 'Informe o mês de referência do vale-transporte.');
            $voltar((string)$colab['codigo_publico']);
        }
        $competencia = (string)$requisicao->post('competencia', '');
        $diario = ServicoFolha::numeroBr($requisicao->post('valor_diario'));
        $dias = (int)$requisicao->post('dias', '0') ?: null;
        $total = ServicoFolha::numeroBr($requisicao->post('valor_total'));
        if ($total === null && $diario !== null && $dias !== null) {
            $total = number_format((float)$diario * $dias, 2, '.', ''); // soma informada, não cálculo trabalhista
        }
        $docId = null;
        $docCodigo = trim((string)$requisicao->post('documento_codigo', ''));
        if ($docCodigo !== '') {
            $doc = $this->repo->documentoPorCodigo($docCodigo);
            if ($doc === null || (int)$doc['colaboradora_id'] !== (int)$colab['id']) {
                Sessao::flash('erro', 'Comprovante não encontrado para esta colaboradora.');
                $voltar((string)$colab['codigo_publico']);
            }
            $docId = (int)$doc['id'];
        }
        $this->repo->salvarValeTransporte((int)$colab['id'], [
            'mes_referencia' => $mes, 'valor_diario' => $diario, 'dias' => $dias, 'valor_total' => $total,
            'data_pagamento' => ServicoFolha::dataValida($requisicao->post('data_pagamento')),
            'competencia' => ServicoFolha::competenciaValida($competencia) ? $competencia : null,
            'desconto_folha' => ServicoFolha::numeroBr($requisicao->post('desconto_folha')),
            'documento_id' => $docId, 'observacoes' => trim((string)$requisicao->post('observacoes', '')) ?: null,
        ], Autenticacao::id());
        Sessao::flash('sucesso', 'Vale-transporte de ' . ServicoFolha::rotuloCompetencia($mes) . ' registrado.');
        $voltar((string)$colab['codigo_publico']);
    }

    // ── Apoio ────────────────────────────────────────────────

    private function exigirGestao(): void
    {
        if (!ServicoFolha::instalado()) {
            Visao::exibir('folha/gestao/nao_instalado', ['titulo' => 'Gestão da Colaboradora', 'portal' => 'gestao']);
        }
        if (!$this->servico->podeGerenciar()) {
            Visao::erro403();
        }
    }

    private function colaboradoraSelecionada(Requisicao $requisicao, array $colabs): ?array
    {
        $codigo = (string)$requisicao->get('colaboradora', '');
        foreach ($colabs as $c) {
            if ($codigo !== '' && $c['codigo_publico'] === $codigo) {
                return $c;
            }
        }
        return $colabs[0] ?? null;
    }

    private function documentoDaRota(Requisicao $requisicao): array
    {
        $documento = $this->repo->documentoPorCodigo($requisicao->parametro('codigo'));
        if ($documento === null) {
            Visao::erro404(); // inclui documento de outra família (filtro do repositório)
        }
        return $documento;
    }

    /** Linha "pagamento" por contracheque publicado com resumo conferido. */
    private function pagamentosRecentes(array $contracheques): array
    {
        $saida = [];
        foreach ($contracheques as $d) {
            if (empty($d['versao_atual_id'])) {
                continue;
            }
            $r = $this->repo->resumoDaVersao((int)$d['versao_atual_id']);
            $saida[] = ['documento' => $d, 'resumo' => $r];
        }
        return $saida;
    }
}
