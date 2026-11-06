<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Visao;
use App\Services\ServicoFolha;

/**
 * Portal da Colaboradora — área da babá ("Meus Pagamentos").
 * Somente leitura: consulta, visualização e download dos PRÓPRIOS documentos
 * publicados. Toda entrega de PDF passa por ServicoFolha::entregar, que
 * verifica a autorização no servidor e registra o evento automaticamente.
 */
final class FolhaController
{
    private ServicoFolha $servico;

    public function __construct()
    {
        $this->servico = new ServicoFolha();
    }

    public function index(Requisicao $requisicao): void
    {
        $colab = $this->colaboradoraOuAviso();
        $repo = $this->servico->repositorio();
        $publicados = $repo->documentos(['colaboradora_id' => (int)$colab['id'], 'status' => 'publicado']);
        $contracheques = array_values(array_filter($publicados, static fn(array $d): bool => $d['tipo'] === 'contracheque'));
        $ultimo = $contracheques[0] ?? null;
        $resumo = $ultimo !== null && !empty($ultimo['versao_atual_id']) ? $repo->resumoDaVersao((int)$ultimo['versao_atual_id']) : null;
        if ($resumo !== null && (int)$resumo['conferido'] !== 1) {
            $resumo = null; // valores não conferidos nunca aparecem para a colaboradora
        }
        $vales = $repo->valesTransporte((int)$colab['id']);
        Visao::exibir('folha/index', [
            'titulo' => 'Meus Pagamentos',
            'portal' => 'colaboradora',
            'colaboradora' => $colab,
            'ultimo' => $ultimo,
            'resumo' => $resumo,
            'ultimoVale' => $vales[0] ?? null,
            'porTipo' => $this->contarPorTipo($publicados),
            'historico' => $this->historicoMensal($publicados),
        ]);
    }

    public function documentos(Requisicao $requisicao): void
    {
        $colab = $this->colaboradoraOuAviso();
        $tipo = (string)$requisicao->get('tipo', '');
        $ano = (string)$requisicao->get('ano', '');
        $filtros = ['colaboradora_id' => (int)$colab['id'], 'status' => 'publicado'];
        if (isset(ServicoFolha::TIPOS[$tipo])) {
            $filtros['tipo'] = $tipo;
        }
        if (preg_match('/^20\d{2}$/', $ano) === 1) {
            $filtros['ano'] = $ano;
        }
        $todos = $this->servico->repositorio()->documentos(['colaboradora_id' => (int)$colab['id'], 'status' => 'publicado']);
        $anos = array_values(array_unique(array_map(static fn(array $d): string => substr((string)$d['competencia'], 0, 4), $todos)));
        Visao::exibir('folha/documentos', [
            'titulo' => 'Meus documentos',
            'portal' => 'colaboradora',
            'colaboradora' => $colab,
            'documentos' => $this->servico->repositorio()->documentos($filtros),
            'tipo' => $filtros['tipo'] ?? '',
            'ano' => $filtros['ano'] ?? '',
            'anos' => $anos,
        ]);
    }

    public function valeTransporte(Requisicao $requisicao): void
    {
        $colab = $this->colaboradoraOuAviso();
        Visao::exibir('folha/vale_transporte', [
            'titulo' => 'Vale-transporte',
            'portal' => 'colaboradora',
            'colaboradora' => $colab,
            'vales' => $this->servico->repositorio()->valesTransporte((int)$colab['id']),
        ]);
    }

    public function documento(Requisicao $requisicao): void
    {
        [$colab, $documento] = $this->documentoAutorizado($requisicao);
        $repo = $this->servico->repositorio();
        $resumo = !empty($documento['versao_atual_id']) ? $repo->resumoDaVersao((int)$documento['versao_atual_id']) : null;
        if ($resumo !== null && (int)$resumo['conferido'] !== 1) {
            $resumo = null;
        }
        Visao::exibir('folha/documento', [
            'titulo' => ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento',
            'portal' => 'colaboradora',
            'colaboradora' => $colab,
            'documento' => $documento,
            'resumo' => $resumo,
            'opVer' => ServicoFolha::novaOperacao(),
            'opBaixar' => ServicoFolha::novaOperacao(),
        ]);
    }

    /** Página do visualizador: o PDF é carregado pelo endpoint "arquivo" (que registra o acesso). */
    public function ver(Requisicao $requisicao): void
    {
        [$colab, $documento] = $this->documentoAutorizado($requisicao);
        $op = ServicoFolha::operacaoValida($requisicao->get('op')) ?: ServicoFolha::novaOperacao();
        Visao::exibir('folha/ver', [
            'titulo' => ServicoFolha::TIPOS[$documento['tipo']] ?? 'Documento',
            'portal' => 'colaboradora',
            'colaboradora' => $colab,
            'documento' => $documento,
            'op' => $op,
            'opBaixar' => ServicoFolha::novaOperacao(),
        ]);
    }

    public function arquivo(Requisicao $requisicao): void
    {
        [$colab, $documento] = $this->documentoAutorizado($requisicao);
        $this->servico->entregar($documento, $colab, 'ver', ServicoFolha::operacaoValida($requisicao->get('op')), $requisicao->ip());
    }

    public function baixar(Requisicao $requisicao): void
    {
        [$colab, $documento] = $this->documentoAutorizado($requisicao);
        $this->servico->entregar($documento, $colab, 'baixar', ServicoFolha::operacaoValida($requisicao->get('op')), $requisicao->ip());
    }

    // ── Apoio ────────────────────────────────────────────────

    /** Vínculo da usuária logada; sem vínculo, mostra a tela explicativa (nunca dados de terceiros). */
    private function colaboradoraOuAviso(): array
    {
        if (!ServicoFolha::instalado()) {
            Visao::exibir('folha/sem_vinculo', ['titulo' => 'Meus Pagamentos', 'portal' => 'colaboradora', 'instalado' => false]);
        }
        $colab = $this->servico->colaboradoraAtual();
        if ($colab === null) {
            Visao::exibir('folha/sem_vinculo', ['titulo' => 'Meus Pagamentos', 'portal' => 'colaboradora', 'instalado' => true]);
        }
        return $colab;
    }

    /** @return array{0:array,1:array} [colaboradora, documento] — 403 (com evento "negado") em qualquer divergência. */
    private function documentoAutorizado(Requisicao $requisicao): array
    {
        $codigo = $requisicao->parametro('codigo');
        $colab = $this->servico->colaboradoraAtual();
        $documento = null;
        try {
            $documento = $this->servico->repositorio()->documentoPorCodigo($codigo);
        } catch (\Throwable) {
            $documento = null;
        }
        if ($colab === null) {
            $this->servico->registrarNegado($documento, 'usuário sem vínculo de colaboradora', $requisicao->ip());
            Visao::erro403();
        }
        if ($documento === null) {
            // Código inexistente OU de outra família (o repositório filtra por família): mesma resposta, sem vazar qual.
            $this->servico->registrarNegado(null, 'documento inexistente ou de outra família: ' . mb_substr($codigo, 0, 20), $requisicao->ip());
            Visao::erro404();
        }
        if ((int)$documento['colaboradora_id'] !== (int)$colab['id']) {
            $this->servico->registrarNegado($documento, 'documento de outra colaboradora', $requisicao->ip());
            Visao::erro403();
        }
        if ($documento['status'] !== 'publicado') {
            $this->servico->registrarNegado($documento, 'documento ' . $documento['status'], $requisicao->ip());
            Visao::erro404();
        }
        return [$colab, $documento];
    }

    private function contarPorTipo(array $documentos): array
    {
        $saida = array_fill_keys(array_keys(ServicoFolha::TIPOS), 0);
        foreach ($documentos as $d) {
            $saida[$d['tipo']] = ($saida[$d['tipo']] ?? 0) + 1;
        }
        return $saida;
    }

    /** Agrupa por ano → mês (inclui meses sem contracheque até o mês atual, como "aguardando"). */
    private function historicoMensal(array $documentos): array
    {
        $porComp = [];
        foreach ($documentos as $d) {
            $porComp[$d['competencia']][] = $d;
        }
        if ($porComp === []) {
            return [];
        }
        $competencias = array_keys($porComp);
        sort($competencias);
        $inicio = new \DateTimeImmutable($competencias[0] . '-01');
        $fim = new \DateTimeImmutable(max(end($competencias), date('Y-m')) . '-01');
        $saida = [];
        for ($c = $fim; $c >= $inicio; $c = $c->modify('-1 month')) {
            $chave = $c->format('Y-m');
            $saida[$c->format('Y')][$chave] = $porComp[$chave] ?? [];
        }
        return $saida;
    }
}
