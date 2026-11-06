<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Portal da Colaboradora — acesso a dados SEMPRE filtrado pela família
 * (RepositorioBase exige :familia_id em toda query). Os eventos de auditoria
 * só têm INSERT: não existe método que altere ou apague um evento.
 */
final class RepositorioFolha extends RepositorioBase
{
    // ── Colaboradoras ────────────────────────────────────────

    public function colaboradoras(): array
    {
        return $this->buscarTodos(
            'SELECT c.*, u.nome, u.email, u.ativo AS usuario_ativo, u.papel
               FROM colaboradoras c JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.familia_id = :familia_id
              ORDER BY c.situacao = "ativa" DESC, u.nome'
        );
    }

    public function colaboradora(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT c.*, u.nome, u.email, u.ativo AS usuario_ativo
               FROM colaboradoras c JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.familia_id = :familia_id AND c.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function colaboradoraPorCodigo(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT c.*, u.nome, u.email, u.ativo AS usuario_ativo
               FROM colaboradoras c JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.familia_id = :familia_id AND c.codigo_publico = :codigo LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    public function colaboradoraDoUsuario(int $usuarioId): ?array
    {
        return $this->buscarUm(
            'SELECT c.*, u.nome, u.email, u.ativo AS usuario_ativo
               FROM colaboradoras c JOIN usuarios u ON u.id = c.usuario_id
              WHERE c.familia_id = :familia_id AND c.usuario_id = :usuario LIMIT 1',
            ['usuario' => $usuarioId]
        );
    }

    /** Cuidadores da família que ainda não têm vínculo cadastrado. */
    public function cuidadoresSemVinculo(): array
    {
        return $this->buscarTodos(
            'SELECT u.id, u.nome, u.email FROM usuarios u
              WHERE u.familia_id = :familia_id AND u.papel = "cuidador" AND u.ativo = 1
                AND NOT EXISTS (SELECT 1 FROM colaboradoras c WHERE c.usuario_id = u.id)
              ORDER BY u.nome'
        );
    }

    public function vincularColaboradora(int $usuarioId, ?string $admissao, ?string $observacoes, int $por): int
    {
        $this->executar(
            'INSERT INTO colaboradoras (familia_id, usuario_id, codigo_publico, admissao, observacoes, criado_por)
             SELECT :familia_id, u.id, :codigo, :admissao, :obs, :por
               FROM usuarios u WHERE u.id = :usuario AND u.familia_id = :familia_id2 AND u.papel = "cuidador"',
            ['usuario' => $usuarioId, 'codigo' => Identificadores::codigoPublico(), 'admissao' => $admissao,
             'obs' => $observacoes, 'por' => $por, 'familia_id2' => $this->familiaId]
        );
        return $this->ultimoId();
    }

    public function atualizarColaboradora(int $id, ?string $admissao, string $situacao, ?string $observacoes): void
    {
        $this->executar(
            'UPDATE colaboradoras SET admissao = :admissao, situacao = :situacao, observacoes = :obs, atualizado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id, 'admissao' => $admissao, 'situacao' => $situacao, 'obs' => $observacoes]
        );
    }

    // ── Permissões de gestão (responsáveis autorizados) ──────

    public function gestoresAutorizados(): array
    {
        return $this->buscarTodos(
            'SELECT p.usuario_id, u.nome, u.email, u.papel FROM folha_permissoes p
               JOIN usuarios u ON u.id = p.usuario_id
              WHERE p.familia_id = :familia_id ORDER BY u.nome'
        );
    }

    public function ehGestorAutorizado(int $usuarioId): bool
    {
        return $this->buscarUm(
            'SELECT 1 FROM folha_permissoes WHERE familia_id = :familia_id AND usuario_id = :u LIMIT 1',
            ['u' => $usuarioId]
        ) !== null;
    }

    public function concederGestao(int $usuarioId, int $por): void
    {
        $this->executar(
            'INSERT IGNORE INTO folha_permissoes (familia_id, usuario_id, concedido_por)
             SELECT :familia_id, u.id, :por FROM usuarios u
              WHERE u.id = :u AND u.familia_id = :familia_id2 AND u.papel IN ("responsavel","admin_familia")',
            ['u' => $usuarioId, 'por' => $por, 'familia_id2' => $this->familiaId]
        );
    }

    public function revogarGestao(int $usuarioId): void
    {
        $this->executar('DELETE FROM folha_permissoes WHERE familia_id = :familia_id AND usuario_id = :u', ['u' => $usuarioId]);
    }

    public function responsaveisDaFamilia(): array
    {
        return $this->buscarTodos(
            'SELECT id, nome, email, papel FROM usuarios
              WHERE familia_id = :familia_id AND ativo = 1 AND papel = "responsavel" ORDER BY nome'
        );
    }

    // ── Documentos ───────────────────────────────────────────

    private const SELECT_DOC = 'SELECT d.*, v.numero AS versao_numero, v.nome_original, v.tamanho, v.sha256, v.caminho,
                                       v.criado_em AS versao_em, c.usuario_id AS colaboradora_usuario_id, u.nome AS colaboradora_nome
                                  FROM folha_documentos d
                                  JOIN colaboradoras c ON c.id = d.colaboradora_id
                                  JOIN usuarios u ON u.id = c.usuario_id
                                  LEFT JOIN folha_versoes v ON v.id = d.versao_atual_id';

    public function criarDocumento(int $colaboradoraId, string $tipo, string $competencia, ?string $descricao, ?string $dataPagamento, ?string $mesReferencia, int $por): int
    {
        $this->executar(
            'INSERT INTO folha_documentos (familia_id, colaboradora_id, codigo_publico, tipo, competencia, descricao, data_pagamento, mes_referencia, criado_por)
             VALUES (:familia_id, :colab, :codigo, :tipo, :competencia, :descricao, :pagamento, :mes, :por)',
            ['colab' => $colaboradoraId, 'codigo' => Identificadores::codigoPublico(), 'tipo' => $tipo, 'competencia' => $competencia,
             'descricao' => $descricao, 'pagamento' => $dataPagamento, 'mes' => $mesReferencia, 'por' => $por]
        );
        return $this->ultimoId();
    }

    public function documento(int $id): ?array
    {
        return $this->buscarUm(self::SELECT_DOC . ' WHERE d.familia_id = :familia_id AND d.id = :id LIMIT 1', ['id' => $id]);
    }

    public function documentoPorCodigo(string $codigo): ?array
    {
        return $this->buscarUm(self::SELECT_DOC . ' WHERE d.familia_id = :familia_id AND d.codigo_publico = :codigo LIMIT 1', ['codigo' => $codigo]);
    }

    /** @param array{colaboradora_id?:int, tipo?:string, status?:string, competencia?:string, publicados?:bool} $filtros */
    public function documentos(array $filtros = [], int $limite = 500): array
    {
        $sql = self::SELECT_DOC . ' WHERE d.familia_id = :familia_id';
        $p = [];
        if (!empty($filtros['colaboradora_id'])) { $sql .= ' AND d.colaboradora_id = :colab'; $p['colab'] = (int)$filtros['colaboradora_id']; }
        if (!empty($filtros['tipo'])) { $sql .= ' AND d.tipo = :tipo'; $p['tipo'] = $filtros['tipo']; }
        if (!empty($filtros['status'])) { $sql .= ' AND d.status = :status'; $p['status'] = $filtros['status']; }
        if (!empty($filtros['competencia'])) { $sql .= ' AND d.competencia = :comp'; $p['comp'] = $filtros['competencia']; }
        if (!empty($filtros['ano'])) { $sql .= ' AND d.competencia LIKE :ano'; $p['ano'] = $filtros['ano'] . '-%'; }
        $sql .= ' ORDER BY d.competencia DESC, d.tipo, d.id DESC LIMIT ' . (int)$limite;
        return $this->buscarTodos($sql, $p);
    }

    /** Documento ativo (não revogado) do mesmo tipo/competência/mês — para evitar duplicidade. */
    public function documentoEquivalente(int $colaboradoraId, string $tipo, string $competencia, ?string $mesReferencia, ?int $excetoId = null): ?array
    {
        return $this->buscarUm(
            'SELECT d.id, d.codigo_publico, d.status FROM folha_documentos d
              WHERE d.familia_id = :familia_id AND d.colaboradora_id = :colab AND d.tipo = :tipo
                AND d.competencia = :competencia AND d.status <> "revogado"
                AND ((:mes IS NULL AND d.mes_referencia IS NULL) OR d.mes_referencia = :mes2)
                AND d.id <> :exceto
              ORDER BY d.id DESC LIMIT 1',
            ['colab' => $colaboradoraId, 'tipo' => $tipo, 'competencia' => $competencia, 'mes' => $mesReferencia,
             'mes2' => $mesReferencia, 'exceto' => $excetoId ?? 0]
        );
    }

    public function atualizarMetadados(int $id, string $competencia, ?string $descricao, ?string $dataPagamento, ?string $mesReferencia): void
    {
        $this->executar(
            'UPDATE folha_documentos SET competencia = :competencia, descricao = :descricao, data_pagamento = :pagamento,
                    mes_referencia = :mes, atualizado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id, 'competencia' => $competencia, 'descricao' => $descricao, 'pagamento' => $dataPagamento, 'mes' => $mesReferencia]
        );
    }

    public function definirVersaoAtual(int $documentoId, int $versaoId): void
    {
        $this->executar(
            'UPDATE folha_documentos SET versao_atual_id = :v, atualizado_em = NOW() WHERE familia_id = :familia_id AND id = :id',
            ['id' => $documentoId, 'v' => $versaoId]
        );
    }

    public function publicar(int $documentoId, int $por): void
    {
        $this->executar(
            'UPDATE folha_documentos SET status = "publicado", publicado_em = NOW(), publicado_por = :por, atualizado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id AND status = "rascunho"',
            ['id' => $documentoId, 'por' => $por]
        );
    }

    public function revogar(int $documentoId, int $por, ?string $motivo): void
    {
        $this->executar(
            'UPDATE folha_documentos SET status = "revogado", revogado_em = NOW(), revogado_por = :por, motivo_revogacao = :motivo, atualizado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $documentoId, 'por' => $por, 'motivo' => $motivo]
        );
        $this->executar(
            'UPDATE folha_versoes SET situacao = "revogada" WHERE familia_id = :familia_id AND documento_id = :id AND situacao = "vigente"',
            ['id' => $documentoId]
        );
    }

    public function excluirRascunho(int $documentoId): array
    {
        $versoes = $this->versoes($documentoId);
        $this->executar('DELETE FROM folha_resumos WHERE familia_id = :familia_id AND documento_id = :id', ['id' => $documentoId]);
        $this->executar('UPDATE folha_documentos SET versao_atual_id = NULL WHERE familia_id = :familia_id AND id = :id', ['id' => $documentoId]);
        $this->executar('DELETE FROM folha_versoes WHERE familia_id = :familia_id AND documento_id = :id', ['id' => $documentoId]);
        $this->executar('DELETE FROM folha_documentos WHERE familia_id = :familia_id AND id = :id AND status = "rascunho"', ['id' => $documentoId]);
        return $versoes;
    }

    // ── Versões ──────────────────────────────────────────────

    public function criarVersao(int $documentoId, int $numero, string $nomeOriginal, string $caminho, int $tamanho, string $sha256, int $por, ?string $motivo = null): int
    {
        $this->executar(
            'INSERT INTO folha_versoes (documento_id, familia_id, numero, nome_original, caminho, tamanho, sha256, enviado_por, motivo)
             VALUES (:doc, :familia_id, :numero, :nome, :caminho, :tamanho, :sha, :por, :motivo)',
            ['doc' => $documentoId, 'numero' => $numero, 'nome' => $nomeOriginal, 'caminho' => $caminho,
             'tamanho' => $tamanho, 'sha' => $sha256, 'por' => $por, 'motivo' => $motivo]
        );
        return $this->ultimoId();
    }

    public function versao(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM folha_versoes WHERE familia_id = :familia_id AND id = :id LIMIT 1', ['id' => $id]);
    }

    public function versoes(int $documentoId): array
    {
        return $this->buscarTodos(
            'SELECT v.*, u.nome AS enviado_por_nome FROM folha_versoes v LEFT JOIN usuarios u ON u.id = v.enviado_por
              WHERE v.familia_id = :familia_id AND v.documento_id = :doc ORDER BY v.numero DESC',
            ['doc' => $documentoId]
        );
    }

    public function marcarSubstituida(int $versaoId, int $por): void
    {
        $this->executar(
            'UPDATE folha_versoes SET situacao = "substituida", substituida_em = NOW(), substituida_por = :por
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $versaoId, 'por' => $por]
        );
    }

    public function versaoComHash(string $sha256): ?array
    {
        return $this->buscarUm(
            'SELECT v.id, v.documento_id, d.codigo_publico, d.tipo, d.competencia FROM folha_versoes v
               JOIN folha_documentos d ON d.id = v.documento_id
              WHERE v.familia_id = :familia_id AND v.sha256 = :sha LIMIT 1',
            ['sha' => $sha256]
        );
    }

    // ── Resumo financeiro ────────────────────────────────────

    public const CAMPOS_RESUMO = ['salario_base', 'salario_bruto', 'horas_extras_qtd', 'horas_extras_valor', 'dsr', 'adicionais',
        'outros_vencimentos', 'inss', 'irrf', 'desconto_vt', 'outros_descontos', 'salario_liquido', 'vt_antecipado', 'total_pago'];

    public function resumoDaVersao(int $versaoId): ?array
    {
        return $this->buscarUm('SELECT * FROM folha_resumos WHERE familia_id = :familia_id AND versao_id = :v LIMIT 1', ['v' => $versaoId]);
    }

    /** @param array<string,mixed> $valores chaves de CAMPOS_RESUMO + data_pagamento, origem, observacoes */
    public function salvarResumo(int $documentoId, int $versaoId, string $competencia, array $valores, bool $conferido, int $por): void
    {
        $colunas = ['documento_id', 'versao_id', 'familia_id', 'competencia', 'data_pagamento', 'origem', 'observacoes', 'conferido', 'conferido_por', 'conferido_em', 'atualizado_em'];
        $p = ['doc' => $documentoId, 'v' => $versaoId, 'competencia' => $competencia,
              'data_pagamento' => $valores['data_pagamento'] ?? null, 'origem' => $valores['origem'] ?? 'manual',
              'observacoes' => $valores['observacoes'] ?? null, 'conferido' => $conferido ? 1 : 0, 'por' => $conferido ? $por : null];
        $valoresSql = [':doc', ':v', ':familia_id', ':competencia', ':data_pagamento', ':origem', ':observacoes', ':conferido', ':por', $conferido ? 'NOW()' : 'NULL', 'NOW()'];
        $atualiza = ['data_pagamento = VALUES(data_pagamento)', 'origem = VALUES(origem)', 'observacoes = VALUES(observacoes)',
            'conferido = VALUES(conferido)', 'conferido_por = VALUES(conferido_por)', 'conferido_em = VALUES(conferido_em)', 'atualizado_em = NOW()'];
        foreach (self::CAMPOS_RESUMO as $campo) {
            $colunas[] = $campo;
            $valoresSql[] = ':' . $campo;
            $p[$campo] = $valores[$campo] ?? null;
            $atualiza[] = "$campo = VALUES($campo)";
        }
        $this->executar(
            'INSERT INTO folha_resumos (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $valoresSql) . ')
             ON DUPLICATE KEY UPDATE ' . implode(', ', $atualiza),
            $p
        );
    }

    // ── Vale-transporte ──────────────────────────────────────

    public function valesTransporte(int $colaboradoraId): array
    {
        return $this->buscarTodos(
            'SELECT vt.*, d.codigo_publico AS documento_codigo, d.status AS documento_status
               FROM folha_vales_transporte vt LEFT JOIN folha_documentos d ON d.id = vt.documento_id
              WHERE vt.familia_id = :familia_id AND vt.colaboradora_id = :colab
              ORDER BY vt.mes_referencia DESC',
            ['colab' => $colaboradoraId]
        );
    }

    public function valeTransporte(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM folha_vales_transporte WHERE familia_id = :familia_id AND id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return int id (upsert por colaboradora + mês de referência) */
    public function salvarValeTransporte(int $colaboradoraId, array $d, int $por): int
    {
        $this->executar(
            'INSERT INTO folha_vales_transporte (familia_id, colaboradora_id, mes_referencia, valor_diario, dias, valor_total,
                    data_pagamento, competencia, desconto_folha, documento_id, observacoes, criado_por)
             VALUES (:familia_id, :colab, :mes, :diario, :dias, :total, :pagamento, :competencia, :desconto, :doc, :obs, :por)
             ON DUPLICATE KEY UPDATE valor_diario = VALUES(valor_diario), dias = VALUES(dias), valor_total = VALUES(valor_total),
                    data_pagamento = VALUES(data_pagamento), competencia = VALUES(competencia), desconto_folha = VALUES(desconto_folha),
                    documento_id = VALUES(documento_id), observacoes = VALUES(observacoes), atualizado_em = NOW()',
            ['colab' => $colaboradoraId, 'mes' => $d['mes_referencia'], 'diario' => $d['valor_diario'], 'dias' => $d['dias'],
             'total' => $d['valor_total'], 'pagamento' => $d['data_pagamento'], 'competencia' => $d['competencia'],
             'desconto' => $d['desconto_folha'], 'doc' => $d['documento_id'], 'obs' => $d['observacoes'], 'por' => $por]
        );
        $linha = $this->buscarUm(
            'SELECT id FROM folha_vales_transporte WHERE familia_id = :familia_id AND colaboradora_id = :colab AND mes_referencia = :mes',
            ['colab' => $colaboradoraId, 'mes' => $d['mes_referencia']]
        );
        return (int)($linha['id'] ?? 0);
    }

    public function excluirValeTransporte(int $id): void
    {
        $this->executar('DELETE FROM folha_vales_transporte WHERE familia_id = :familia_id AND id = :id', ['id' => $id]);
    }

    // ── Eventos (auditoria — somente inserção) ───────────────

    public function registrarEvento(array $e): void
    {
        $this->executar(
            'INSERT INTO folha_eventos (familia_id, colaboradora_id, documento_id, versao_id, usuario_id, tipo, categoria, resultado,
                    operacao, competencia, ip, detalhe, criado_em)
             VALUES (:familia_id, :colab, :doc, :versao, :usuario, :tipo, :categoria, :resultado, :operacao, :competencia, :ip, :detalhe, UTC_TIMESTAMP())',
            ['colab' => $e['colaboradora_id'] ?? null, 'doc' => $e['documento_id'] ?? null, 'versao' => $e['versao_id'] ?? null,
             'usuario' => $e['usuario_id'] ?? null, 'tipo' => $e['tipo'], 'categoria' => $e['categoria'] ?? 'consulta',
             'resultado' => $e['resultado'] ?? 'ok', 'operacao' => $e['operacao'] ?? null, 'competencia' => $e['competencia'] ?? null,
             'ip' => $e['ip'] ?? null, 'detalhe' => isset($e['detalhe']) ? mb_substr((string)$e['detalhe'], 0, 200) : null]
        );
    }

    /** Já existe evento bem-sucedido desta operação (mesma versão, tipo e usuário)? */
    public function eventoDaOperacao(int $versaoId, string $tipo, string $operacao, int $usuarioId): bool
    {
        return $this->buscarUm(
            'SELECT 1 FROM folha_eventos WHERE familia_id = :familia_id AND versao_id = :v AND tipo = :tipo
                AND operacao = :op AND usuario_id = :u AND resultado = "ok" LIMIT 1',
            ['v' => $versaoId, 'tipo' => $tipo, 'op' => $operacao, 'u' => $usuarioId]
        ) !== null;
    }

    /** Evento bem-sucedido do mesmo tipo/versão/usuário nos últimos N segundos (requisições técnicas repetidas). */
    public function eventoRecente(int $versaoId, string $tipo, int $usuarioId, int $segundos): bool
    {
        return $this->buscarUm(
            'SELECT 1 FROM folha_eventos WHERE familia_id = :familia_id AND versao_id = :v AND tipo = :tipo AND usuario_id = :u
                AND resultado = "ok" AND criado_em >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL :seg SECOND) LIMIT 1',
            ['v' => $versaoId, 'tipo' => $tipo, 'u' => $usuarioId, 'seg' => $segundos]
        ) !== null;
    }

    public function eventosDoDocumento(int $documentoId): array
    {
        return $this->buscarTodos(
            'SELECT e.*, u.nome AS usuario_nome, v.numero AS versao_numero FROM folha_eventos e
               LEFT JOIN usuarios u ON u.id = e.usuario_id LEFT JOIN folha_versoes v ON v.id = e.versao_id
              WHERE e.familia_id = :familia_id AND e.documento_id = :doc ORDER BY e.criado_em DESC, e.id DESC',
            ['doc' => $documentoId]
        );
    }

    public function eventosRecentes(int $limite = 100, ?int $colaboradoraId = null): array
    {
        $sql = 'SELECT e.*, u.nome AS usuario_nome, v.numero AS versao_numero, d.tipo AS documento_tipo, d.codigo_publico AS documento_codigo
                  FROM folha_eventos e
                  LEFT JOIN usuarios u ON u.id = e.usuario_id LEFT JOIN folha_versoes v ON v.id = e.versao_id
                  LEFT JOIN folha_documentos d ON d.id = e.documento_id
                 WHERE e.familia_id = :familia_id';
        $p = [];
        if ($colaboradoraId !== null) { $sql .= ' AND e.colaboradora_id = :colab'; $p['colab'] = $colaboradoraId; }
        return $this->buscarTodos($sql . ' ORDER BY e.criado_em DESC, e.id DESC LIMIT ' . (int)$limite, $p);
    }

    /** Indicadores por documento: primeiro/último acesso, aberturas e downloads (eventos "ok" de consulta). */
    public function indicadoresDeAcesso(int $documentoId): array
    {
        $linha = $this->buscarUm(
            'SELECT MIN(criado_em) AS primeiro, MAX(criado_em) AS ultimo,
                    SUM(tipo = "aberto") AS aberturas, SUM(tipo = "baixado") AS downloads
               FROM folha_eventos WHERE familia_id = :familia_id AND documento_id = :doc
                AND categoria = "consulta" AND resultado = "ok"',
            ['doc' => $documentoId]
        );
        return ['primeiro' => $linha['primeiro'] ?? null, 'ultimo' => $linha['ultimo'] ?? null,
                'aberturas' => (int)($linha['aberturas'] ?? 0), 'downloads' => (int)($linha['downloads'] ?? 0)];
    }

    /** Último acesso (consulta ok) a qualquer documento da colaboradora. */
    public function ultimoAcesso(int $colaboradoraId): ?string
    {
        $l = $this->buscarUm(
            'SELECT MAX(criado_em) AS ultimo FROM folha_eventos WHERE familia_id = :familia_id AND colaboradora_id = :c AND categoria = "consulta" AND resultado = "ok"',
            ['c' => $colaboradoraId]
        );
        return $l['ultimo'] ?? null;
    }

    public function contagens(int $colaboradoraId): array
    {
        $l = $this->buscarUm(
            'SELECT COUNT(*) AS total, SUM(status = "publicado") AS publicados, SUM(status = "rascunho") AS rascunhos,
                    MAX(CASE WHEN status = "publicado" AND tipo = "contracheque" THEN competencia END) AS ultima_competencia
               FROM folha_documentos WHERE familia_id = :familia_id AND colaboradora_id = :c',
            ['c' => $colaboradoraId]
        );
        return ['total' => (int)($l['total'] ?? 0), 'publicados' => (int)($l['publicados'] ?? 0),
                'rascunhos' => (int)($l['rascunhos'] ?? 0), 'ultima_competencia' => $l['ultima_competencia'] ?? null];
    }
}
