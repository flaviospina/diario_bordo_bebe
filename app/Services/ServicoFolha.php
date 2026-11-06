<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\Autenticacao;
use App\Core\LeitorPdf;
use App\Core\Sessao;
use App\Repositories\RepositorioFolha;

/**
 * Portal da Colaboradora — regras de negócio.
 *
 * Permissões (equivalentes a payroll.*):
 *  - gerir/enviar/publicar/revogar/ver auditoria: admin_familia da família, ou
 *    responsável explicitamente autorizado (folha_permissoes).
 *  - ver/baixar os próprios documentos: usuário vinculado como colaboradora.
 *  Super admin da plataforma NÃO acessa (dados financeiros são da família).
 *
 * O eSocial continua sendo a fonte oficial: aqui só se guardam PDFs e valores
 * conferidos pelo empregador. Nenhum cálculo tributário é feito.
 */
final class ServicoFolha
{
    public const TIPOS = [
        'contracheque' => 'Contracheque',
        'comprovante_pagamento' => 'Comprovante de pagamento',
        'vale_transporte' => 'Vale-transporte',
        'horas_extras' => 'Horas extras',
        'ferias' => 'Férias',
        'decimo_terceiro' => '13º salário',
        'informe_rendimentos' => 'Informe de rendimentos',
        'comprovante_previdenciario' => 'Comprovante previdenciário',
        'complementar' => 'Documento trabalhista complementar',
    ];

    public const STATUS = ['rascunho' => 'Rascunho', 'publicado' => 'Disponível', 'revogado' => 'Revogado'];

    public const EVENTOS = [
        'publicado' => 'Documento disponibilizado', 'aberto' => 'Documento acessado', 'baixado' => 'Documento baixado',
        'substituido' => 'Documento substituído', 'revogado' => 'Documento revogado', 'negado' => 'Acesso negado',
    ];

    /** Janela em que requisições repetidas do navegador para a mesma versão contam como uma só abertura. */
    private const JANELA_DEDUP_SEGUNDOS = 20;
    private const LIMITE_REQUISICOES_MINUTO = 60;

    private RepositorioFolha $repo;

    public function __construct(private readonly ?int $familiaId = null)
    {
        $this->repo = new RepositorioFolha($familiaId);
    }

    public function repositorio(): RepositorioFolha
    {
        return $this->repo;
    }

    // ── Permissões ───────────────────────────────────────────

    /** payroll.manage / upload / publish / revoke / view_audit */
    public function podeGerenciar(): bool
    {
        $papel = Autenticacao::papel();
        if ($papel === 'admin_familia') {
            return true;
        }
        if ($papel === 'responsavel') {
            try {
                return $this->repo->ehGestorAutorizado(Autenticacao::id());
            } catch (\Throwable) {
                return false;
            }
        }
        return false;
    }

    /** payroll.view_own / download_own: vínculo do usuário logado (null = não é colaboradora). */
    public function colaboradoraAtual(): ?array
    {
        if (!Autenticacao::temPapel('cuidador')) {
            return null;
        }
        try {
            return $this->repo->colaboradoraDoUsuario(Autenticacao::id());
        } catch (\Throwable) {
            return null; // tabela ainda não migrada
        }
    }

    /** O módulo está instalado (migração 0017 aplicada)? */
    public static function instalado(): bool
    {
        try {
            \App\Core\BancoDados::conexao()->query('SELECT 1 FROM folha_documentos LIMIT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Upload e armazenamento privado ───────────────────────

    public static function limiteBytes(): int
    {
        $mb = (int)Ambiente::obter('FOLHA_MAX_MB', '10');
        return max(1, $mb) * 1048576;
    }

    /**
     * Valida o PDF enviado e o guarda em storage/folha/{familia}/ com nome aleatório.
     * @return array{erro:?string, caminho?:string, sha256?:string, tamanho?:int, nome?:string}
     */
    public function receberPdf(?array $arquivo): array
    {
        if ($arquivo === null || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['erro' => 'Selecione o arquivo PDF.'];
        }
        if (($arquivo['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return ['erro' => ($arquivo['error'] === UPLOAD_ERR_INI_SIZE || $arquivo['error'] === UPLOAD_ERR_FORM_SIZE)
                ? 'Arquivo grande demais para o servidor.' : 'Falha no envio do arquivo (código ' . (int)$arquivo['error'] . ').'];
        }
        $tmp = (string)$arquivo['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return ['erro' => 'Envio inválido.'];
        }
        $nomeOriginal = mb_substr(preg_replace('/[^\p{L}\p{N} ._()-]/u', '_', (string)($arquivo['name'] ?? 'documento.pdf')) ?? 'documento.pdf', 0, 255);
        if (!preg_match('/\.pdf$/i', $nomeOriginal)) {
            return ['erro' => 'Só arquivos .pdf são aceitos.'];
        }
        $erro = LeitorPdf::validar($tmp, self::limiteBytes());
        if ($erro !== null) {
            return ['erro' => $erro];
        }
        $familia = $this->familiaId ?? Autenticacao::familiaId();
        $pasta = STORAGE_PATH . '/folha/' . $familia;
        if (!is_dir($pasta) && !mkdir($pasta, 0750, true) && !is_dir($pasta)) {
            return ['erro' => 'Armazenamento privado indisponível.'];
        }
        if (!is_file(STORAGE_PATH . '/folha/.htaccess')) {
            @file_put_contents(STORAGE_PATH . '/folha/.htaccess', "Require all denied\n");
        }
        do {
            $nome = bin2hex(random_bytes(16)) . '.pdf';
        } while (is_file($pasta . '/' . $nome)); // nunca sobrescreve
        $destino = $pasta . '/' . $nome;
        if (!move_uploaded_file($tmp, $destino)) {
            return ['erro' => 'Não foi possível guardar o arquivo.'];
        }
        @chmod($destino, 0640);
        return ['erro' => null, 'caminho' => 'folha/' . $familia . '/' . $nome, 'sha256' => hash_file('sha256', $destino),
                'tamanho' => (int)filesize($destino), 'nome' => $nomeOriginal];
    }

    public function caminhoAbsoluto(array $versao): string
    {
        return STORAGE_PATH . '/' . (string)$versao['caminho'];
    }

    // ── Ciclo de vida do documento ───────────────────────────

    /** Cria o documento em rascunho com a versão 1. @return array{erro:?string, documento?:array, sugestao?:array} */
    public function criarDocumento(int $colaboradoraId, string $tipo, string $competencia, ?string $descricao, ?string $dataPagamento, ?string $mesReferencia, ?array $arquivo): array
    {
        if (!isset(self::TIPOS[$tipo])) {
            return ['erro' => 'Tipo de documento inválido.'];
        }
        if (!self::competenciaValida($competencia)) {
            return ['erro' => 'Informe a competência (mês/ano).'];
        }
        if ($mesReferencia !== null && !self::competenciaValida($mesReferencia)) {
            return ['erro' => 'Mês de referência inválido.'];
        }
        $colab = $this->repo->colaboradora($colaboradoraId);
        if ($colab === null) {
            return ['erro' => 'Colaboradora não encontrada.'];
        }
        $equivalente = $this->repo->documentoEquivalente($colaboradoraId, $tipo, $competencia, $mesReferencia);
        if ($equivalente !== null) {
            return ['erro' => 'Já existe ' . mb_strtolower(self::TIPOS[$tipo]) . ' de ' . self::rotuloCompetencia($competencia)
                . ' (' . self::STATUS[$equivalente['status']] . '). Abra-o para substituir o PDF ou revogue-o antes de criar outro.',
                'equivalente' => $equivalente];
        }
        $recebido = $this->receberPdf($arquivo);
        if ($recebido['erro'] !== null) {
            return ['erro' => $recebido['erro']];
        }
        $usuario = Autenticacao::id();
        $bd = \App\Core\BancoDados::conexao();
        $bd->beginTransaction();
        try {
            $docId = $this->repo->criarDocumento($colaboradoraId, $tipo, $competencia, $descricao, $dataPagamento, $mesReferencia, $usuario);
            $versaoId = $this->repo->criarVersao($docId, 1, $recebido['nome'], $recebido['caminho'], $recebido['tamanho'], $recebido['sha256'], $usuario);
            $this->repo->definirVersaoAtual($docId, $versaoId);
            $bd->commit();
        } catch (\Throwable $e) {
            $bd->rollBack();
            @unlink(STORAGE_PATH . '/' . $recebido['caminho']);
            throw $e;
        }
        $duplicado = $this->repo->versaoComHash($recebido['sha256']);
        $aviso = ($duplicado !== null && (int)$duplicado['documento_id'] !== $docId)
            ? 'Atenção: este mesmo arquivo já foi enviado em ' . self::TIPOS[$duplicado['tipo']] . ' de ' . self::rotuloCompetencia($duplicado['competencia']) . '.'
            : null;
        $documento = $this->repo->documento($docId);
        // Sugestões extraídas do PDF (complementares; nunca viram valores sem conferência)
        $sugestao = $tipo === 'contracheque' ? $this->sugerirValores($documento) : [];
        if ($sugestao !== []) {
            $this->repo->salvarResumo($docId, $versaoId, $competencia, $sugestao + ['origem' => 'extraido'], false, $usuario);
        }
        return ['erro' => null, 'documento' => $documento, 'aviso' => $aviso, 'sugestao' => $sugestao];
    }

    /** @return ?string erro */
    public function publicar(array $documento, string $ip): ?string
    {
        if ($documento['status'] !== 'rascunho') {
            return 'Só rascunhos podem ser publicados.';
        }
        if (empty($documento['versao_atual_id'])) {
            return 'O documento não tem PDF.';
        }
        $resumo = $this->repo->resumoDaVersao((int)$documento['versao_atual_id']);
        if ($documento['tipo'] === 'contracheque' && ($resumo === null || (int)$resumo['conferido'] !== 1)) {
            return 'Confira e salve os valores do contracheque antes de publicar.';
        }
        $this->repo->publicar((int)$documento['id'], Autenticacao::id());
        $this->repo->registrarEvento([
            'colaboradora_id' => $documento['colaboradora_id'], 'documento_id' => $documento['id'], 'versao_id' => $documento['versao_atual_id'],
            'usuario_id' => Autenticacao::id(), 'tipo' => 'publicado', 'categoria' => 'administrativo', 'competencia' => $documento['competencia'], 'ip' => $ip,
        ]);
        $this->notificarColaboradora($documento);
        return null;
    }

    /** Nova versão do PDF (a anterior fica no histórico, inacessível à colaboradora). @return ?string erro */
    public function substituir(array $documento, ?array $arquivo, ?string $motivo, string $ip): ?string
    {
        if ($documento['status'] === 'revogado') {
            return 'Documento revogado não pode ser substituído — crie um novo.';
        }
        $recebido = $this->receberPdf($arquivo);
        if ($recebido['erro'] !== null) {
            return $recebido['erro'];
        }
        $usuario = Autenticacao::id();
        $versaoAnterior = (int)$documento['versao_atual_id'];
        $numero = (int)$documento['versao_numero'] + 1;
        $bd = \App\Core\BancoDados::conexao();
        $bd->beginTransaction();
        try {
            $nova = $this->repo->criarVersao((int)$documento['id'], $numero, $recebido['nome'], $recebido['caminho'], $recebido['tamanho'], $recebido['sha256'], $usuario, $motivo);
            $this->repo->marcarSubstituida($versaoAnterior, $usuario);
            $this->repo->definirVersaoAtual((int)$documento['id'], $nova);
            // Resumo: copia os valores da versão anterior como NÃO conferidos (o gestor reconfere)
            $resumo = $this->repo->resumoDaVersao($versaoAnterior);
            if ($resumo !== null) {
                $valores = array_intersect_key($resumo, array_flip(RepositorioFolha::CAMPOS_RESUMO));
                $valores['data_pagamento'] = $resumo['data_pagamento'];
                $valores['origem'] = $resumo['origem'];
                $valores['observacoes'] = $resumo['observacoes'];
                $this->repo->salvarResumo((int)$documento['id'], $nova, (string)$documento['competencia'], $valores, false, $usuario);
            }
            $bd->commit();
        } catch (\Throwable $e) {
            $bd->rollBack();
            @unlink(STORAGE_PATH . '/' . $recebido['caminho']);
            throw $e;
        }
        $this->repo->registrarEvento([
            'colaboradora_id' => $documento['colaboradora_id'], 'documento_id' => $documento['id'], 'versao_id' => $nova,
            'usuario_id' => $usuario, 'tipo' => 'substituido', 'categoria' => 'administrativo', 'competencia' => $documento['competencia'],
            'ip' => $ip, 'detalhe' => 'v' . ($numero - 1) . ' → v' . $numero . ($motivo ? ': ' . $motivo : ''),
        ]);
        if ($documento['status'] === 'publicado') {
            $this->notificarColaboradora($documento, true);
        }
        return null;
    }

    public function revogar(array $documento, ?string $motivo, string $ip): ?string
    {
        if ($documento['status'] === 'revogado') {
            return 'Documento já revogado.';
        }
        $this->repo->revogar((int)$documento['id'], Autenticacao::id(), $motivo);
        $this->repo->registrarEvento([
            'colaboradora_id' => $documento['colaboradora_id'], 'documento_id' => $documento['id'], 'versao_id' => $documento['versao_atual_id'],
            'usuario_id' => Autenticacao::id(), 'tipo' => 'revogado', 'categoria' => 'administrativo', 'competencia' => $documento['competencia'],
            'ip' => $ip, 'detalhe' => $motivo,
        ]);
        return null;
    }

    /** Apaga um rascunho (nunca algo já publicado) e seus arquivos. */
    public function excluirRascunho(array $documento): ?string
    {
        if ($documento['status'] !== 'rascunho') {
            return 'Só rascunhos podem ser excluídos; documentos publicados são revogados.';
        }
        foreach ($this->repo->excluirRascunho((int)$documento['id']) as $versao) {
            @unlink(STORAGE_PATH . '/' . $versao['caminho']);
        }
        return null;
    }

    private function notificarColaboradora(array $documento, bool $atualizacao = false): void
    {
        $colab = $this->repo->colaboradora((int)$documento['colaboradora_id']);
        if ($colab === null || (int)$colab['usuario_ativo'] !== 1) {
            return;
        }
        $tipo = self::TIPOS[$documento['tipo']] ?? 'Documento';
        $comp = self::rotuloCompetencia((string)$documento['competencia']);
        $titulo = $atualizacao ? "$tipo atualizado" : "Novo $tipo disponível";
        $mensagem = ($atualizacao ? "Uma versão corrigida do seu " : "Seu ") . mb_strtolower($tipo) . " referente a $comp está disponível no Diário do Bebê.";
        $url = url('portal.documento', ['codigo' => $documento['codigo_publico']]);
        try {
            (new ServicoAvisos($this->familiaId))->avisar([(int)$colab['usuario_id']], 'folha', $titulo, $mensagem, $url);
        } catch (\Throwable $e) {
            error_log('Aviso da folha falhou: ' . $e->getMessage());
        }
        try {
            $email = new ServicoEmail();
            $html = '<p>Olá, ' . e((string)$colab['nome']) . '.</p><p>' . e($mensagem) . '</p>'
                . '<p>Entre com o seu login de sempre e abra <strong>Meus Pagamentos</strong>.</p>'
                . ServicoEmail::botao('Abrir no Diário do Bebê', url_absoluta('portal.documento', ['codigo' => $documento['codigo_publico']]))
                . '<p style="font-size:12px;color:#7D776C">Por segurança, valores e dados pessoais não vão no e-mail.</p>';
            $email->enviarHtml((string)$colab['email'], $titulo . ' — ' . $comp, $html, $mensagem, 'folha', (int)$documento['id'], $this->familiaId ?? Autenticacao::familiaId());
        } catch (\Throwable $e) {
            error_log('E-mail da folha falhou: ' . $e->getMessage());
        }
    }

    // ── Entrega e auditoria automática ───────────────────────

    /**
     * Entrega o PDF vigente à colaboradora (inline ou download) e registra o
     * evento SÓ depois de o arquivo ter sido enviado com sucesso. Requisições
     * técnicas repetidas (mesma operação, ou mesma versão em poucos segundos)
     * não multiplicam o evento.
     */
    public function entregar(array $documento, array $colaboradora, string $modo, string $operacao, string $ip): never
    {
        $usuario = Autenticacao::id();
        $tipoEvento = $modo === 'baixar' ? 'baixado' : 'aberto';
        $base = ['colaboradora_id' => $colaboradora['id'], 'documento_id' => $documento['id'], 'usuario_id' => $usuario,
                 'competencia' => $documento['competencia'], 'ip' => $ip, 'operacao' => $operacao !== '' ? $operacao : null];

        if ((int)$documento['colaboradora_id'] !== (int)$colaboradora['id'] || $documento['status'] !== 'publicado' || empty($documento['versao_atual_id'])) {
            $this->repo->registrarEvento($base + ['tipo' => 'negado', 'categoria' => 'seguranca', 'resultado' => 'negado', 'detalhe' => 'documento indisponível']);
            \App\Core\Visao::erro403();
        }
        $this->limitarAcessos();
        $versao = $this->repo->versao((int)$documento['versao_atual_id']);
        $caminho = $versao !== null ? $this->caminhoAbsoluto($versao) : '';
        if ($versao === null || $versao['situacao'] !== 'vigente' || !is_file($caminho) || !is_readable($caminho)) {
            $this->repo->registrarEvento($base + ['tipo' => $tipoEvento, 'versao_id' => $versao['id'] ?? null, 'resultado' => 'erro', 'detalhe' => 'arquivo indisponível no armazenamento']);
            \App\Core\Visao::exibir('erros/500', ['titulo' => 'Documento indisponível'], 'base', 500);
        }
        $nomeArquivo = self::nomeParaDownload($documento);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string)filesize($caminho));
        header('Content-Disposition: ' . ($modo === 'baixar' ? 'attachment' : 'inline') . '; filename="' . $nomeArquivo . '"');
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
        $enviado = readfile($caminho);
        if ($enviado === false) {
            $this->repo->registrarEvento($base + ['tipo' => $tipoEvento, 'versao_id' => $versao['id'], 'resultado' => 'erro', 'detalhe' => 'falha na leitura do arquivo']);
            exit;
        }
        $jaRegistrado = $operacao !== ''
            ? $this->repo->eventoDaOperacao((int)$versao['id'], $tipoEvento, $operacao, $usuario)
            : $this->repo->eventoRecente((int)$versao['id'], $tipoEvento, $usuario, self::JANELA_DEDUP_SEGUNDOS);
        if (!$jaRegistrado) {
            $this->repo->registrarEvento($base + ['tipo' => $tipoEvento, 'versao_id' => $versao['id'], 'categoria' => 'consulta', 'resultado' => 'ok']);
        }
        exit;
    }

    /** Tentativa de acesso negada (sem vínculo, outra família, documento alheio). */
    public function registrarNegado(?array $documento, string $detalhe, string $ip): void
    {
        try {
            $this->repo->registrarEvento([
                'colaboradora_id' => $documento['colaboradora_id'] ?? null, 'documento_id' => $documento['id'] ?? null,
                'versao_id' => $documento['versao_atual_id'] ?? null, 'usuario_id' => Autenticacao::id() ?: null,
                'tipo' => 'negado', 'categoria' => 'seguranca', 'resultado' => 'negado', 'competencia' => $documento['competencia'] ?? null,
                'ip' => $ip, 'detalhe' => $detalhe,
            ]);
        } catch (\Throwable) {
            // auditoria nunca derruba a resposta de erro
        }
    }

    /** Limite simples por sessão contra varredura de documentos. */
    private function limitarAcessos(): void
    {
        $agora = time();
        $marcas = array_filter((array)Sessao::obter('_folha_acessos', []), static fn($t) => (int)$t > $agora - 60);
        if (count($marcas) >= self::LIMITE_REQUISICOES_MINUTO) {
            http_response_code(429);
            header('Retry-After: 60');
            exit('Muitas solicitações. Aguarde um minuto.');
        }
        $marcas[] = $agora;
        Sessao::definir('_folha_acessos', array_values($marcas));
    }

    public static function novaOperacao(): string
    {
        return bin2hex(random_bytes(8));
    }

    public static function operacaoValida(?string $op): string
    {
        return is_string($op) && preg_match('/^[a-f0-9]{16}$/', $op) === 1 ? $op : '';
    }

    public static function nomeParaDownload(array $documento): string
    {
        $tipo = preg_replace('/[^a-z0-9]+/', '-', self::semAcentos(mb_strtolower(self::TIPOS[$documento['tipo']] ?? 'documento')));
        return trim((string)$tipo, '-') . '-' . str_replace('-', '', (string)$documento['competencia']) . '-v' . (int)($documento['versao_numero'] ?? 1) . '.pdf';
    }

    // ── Extração best-effort (sugestões, sempre conferidas) ──

    /** @return array<string,string> campo => valor sugerido ("1234.56" / data "Y-m-d") */
    public function sugerirValores(array $documento): array
    {
        if (empty($documento['caminho'])) {
            return [];
        }
        $conteudo = @file_get_contents(STORAGE_PATH . '/' . $documento['caminho']);
        if ($conteudo === false) {
            return [];
        }
        $texto = LeitorPdf::extrairTexto($conteudo);
        return self::valoresDoTexto($texto);
    }

    /** Procura rótulos conhecidos do contracheque do eSocial no texto extraído. */
    public static function valoresDoTexto(string $texto): array
    {
        if (trim($texto) === '') {
            return [];
        }
        $plano = self::semAcentos(mb_strtolower($texto));
        $valor = '([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2}|[0-9]+,[0-9]{2})';
        $padroes = [
            'salario_liquido' => '/(?:valor\s+)?l[ií]quido(?:\s+a\s+receber)?[^0-9]{0,40}' . $valor . '/',
            'salario_bruto' => '/(?:total\s+de\s+vencimentos|salario\s+bruto|total\s+bruto|vencimentos)[^0-9]{0,40}' . $valor . '/',
            'salario_base' => '/salario(?:\s+base|\s+contratual)?(?:\s+mensal)?[^0-9]{0,30}' . $valor . '/',
            'inss' => '/inss[^0-9]{0,60}' . $valor . '/',
            'irrf' => '/(?:irrf|irpf|imposto\s+de\s+renda)[^0-9]{0,60}' . $valor . '/',
            'desconto_vt' => '/(?:desconto\s+)?vale[- ]transporte[^0-9]{0,60}' . $valor . '/',
            'horas_extras_valor' => '/horas?\s+extras?[^0-9]{0,60}' . $valor . '/',
            'dsr' => '/(?:dsr|descanso\s+semanal)[^0-9]{0,60}' . $valor . '/',
            'outros_descontos' => '/total\s+de\s+descontos[^0-9]{0,40}' . $valor . '/',
        ];
        $saida = [];
        foreach ($padroes as $campo => $padrao) {
            if (preg_match($padrao, $plano, $m) === 1) {
                $saida[$campo] = self::numeroBr($m[1]);
            }
        }
        if (preg_match('/compet[eê]ncia[^0-9]{0,20}(0[1-9]|1[0-2])\/(20[0-9]{2})/', $plano, $m) === 1) {
            $saida['competencia'] = $m[2] . '-' . $m[1];
        }
        if (preg_match('/(?:data\s+de\s+pagamento|pago\s+em|pagamento)[^0-9]{0,20}([0-3][0-9])\/(0[1-9]|1[0-2])\/(20[0-9]{2})/', $plano, $m) === 1) {
            $saida['data_pagamento'] = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return $saida;
    }

    // ── Formatação e validação ───────────────────────────────

    public static function competenciaValida(?string $c): bool
    {
        return is_string($c) && preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/', $c) === 1;
    }

    public static function rotuloCompetencia(?string $c, bool $curto = false): string
    {
        if ($c === null || !self::competenciaValida($c)) {
            return '—';
        }
        $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $mes = (int)substr($c, 5, 2);
        $nome = $meses[$mes - 1];
        return $curto ? ucfirst(substr($nome, 0, 3)) . '/' . substr($c, 0, 4) : $nome . ' de ' . substr($c, 0, 4);
    }

    /** "1.234,56" / "1234.56" → "1234.56"; vazio → null. */
    public static function numeroBr(?string $texto): ?string
    {
        $t = trim((string)$texto);
        if ($t === '') {
            return null;
        }
        $t = str_replace(['R$', ' '], '', $t);
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $t) || preg_match('/^-?\d+,\d{1,2}$/', $t)) {
            $t = str_replace('.', '', $t);
            $t = str_replace(',', '.', $t);
        }
        return is_numeric($t) ? number_format((float)$t, 2, '.', '') : null;
    }

    public static function dinheiro(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'não informado';
        }
        return 'R$ ' . number_format((float)$valor, 2, ',', '.');
    }

    /** Data/hora UTC do banco → "dd/mm/aaaa HH:ii" em São Paulo. */
    public static function horaLocal(?string $utc, string $formato = 'd/m/Y H:i'): string
    {
        if ($utc === null || $utc === '') {
            return '—';
        }
        try {
            return (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('America/Sao_Paulo'))->format($formato);
        } catch (\Throwable) {
            return $utc;
        }
    }

    public static function semAcentos(string $s): string
    {
        $r = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $r === false ? $s : $r;
    }

    public static function dataValida(?string $d): ?string
    {
        return is_string($d) && preg_match('/^20[0-9]{2}-[0-1][0-9]-[0-3][0-9]$/', $d) === 1 && strtotime($d) !== false ? $d : null;
    }
}
