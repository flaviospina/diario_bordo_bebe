<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Registros efetivos do diário.
 * Regras estruturais garantidas aqui:
 *  - idempotência por uuid_cliente (sincronização offline nunca duplica);
 *  - exclusão é sempre LÓGICA (excluido_em) — nada é apagado do banco.
 */
final class RepositorioRegistros extends RepositorioBase
{
    public function buscarPorCodigo(string $codigoPublico): ?array
    {
        return $this->buscarUm(
            'SELECT r.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.grupo AS categoria_grupo,
                    c.icone AS categoria_icone, c.schema_campos, cr.nome AS crianca_nome, cr.slug AS crianca_slug,
                    u.nome AS usuario_nome
               FROM registros r
               JOIN categorias c ON c.id = r.categoria_id
               JOIN criancas cr ON cr.id = r.crianca_id
               JOIN usuarios u ON u.id = r.usuario_id
              WHERE r.familia_id = :familia_id AND r.codigo_publico = :codigo
              LIMIT 1',
            ['codigo' => $codigoPublico]
        );
    }

    public function buscarPorUuid(string $uuidCliente): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM registros WHERE familia_id = :familia_id AND uuid_cliente = :uuid LIMIT 1',
            ['uuid' => $uuidCliente]
        );
    }

    /** @return array<int,array<string,mixed>> registros (não excluídos) do dia, em ordem */
    public function listarDoDia(int $criancaId, string $data): array
    {
        return $this->buscarTodos(
            'SELECT r.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.grupo AS categoria_grupo,
                    c.icone AS categoria_icone, c.cor AS categoria_cor, u.nome AS usuario_nome
               FROM registros r
               JOIN categorias c ON c.id = r.categoria_id
               JOIN usuarios u ON u.id = r.usuario_id
              WHERE r.familia_id = :familia_id AND r.crianca_id = :crianca
                AND r.inicio >= :inicio AND r.inicio < :fim
                AND r.excluido_em IS NULL
              ORDER BY r.inicio, r.id',
            ['crianca' => $criancaId, 'inicio' => $data . ' 00:00:00', 'fim' => $data . ' 23:59:59']
        );
    }

    /** Linha do tempo paginada, com filtros opcionais. */
    public function linhaDoTempo(int $criancaId, ?string $categoriaSlug, ?string $dataInicio, ?string $dataFim, int $limite = 100): array
    {
        $sql = 'SELECT r.*, c.nome AS categoria_nome, c.slug AS categoria_slug, c.grupo AS categoria_grupo,
                       c.icone AS categoria_icone, u.nome AS usuario_nome
                  FROM registros r
                  JOIN categorias c ON c.id = r.categoria_id
                  JOIN usuarios u ON u.id = r.usuario_id
                 WHERE r.familia_id = :familia_id AND r.crianca_id = :crianca AND r.excluido_em IS NULL';
        $parametros = ['crianca' => $criancaId];
        if ($categoriaSlug !== null && $categoriaSlug !== '') {
            $sql .= ' AND c.slug = :slug';
            $parametros['slug'] = $categoriaSlug;
        }
        if ($dataInicio !== null) {
            $sql .= ' AND r.inicio >= :de';
            $parametros['de'] = $dataInicio . ' 00:00:00';
        }
        if ($dataFim !== null) {
            $sql .= ' AND r.inicio <= :ate';
            $parametros['ate'] = $dataFim . ' 23:59:59';
        }
        $sql .= ' ORDER BY r.inicio DESC, r.id DESC LIMIT ' . max(1, min(500, $limite));
        return $this->buscarTodos($sql, $parametros);
    }

    /**
     * Cria um registro. Se o uuid_cliente já existir, devolve o existente
     * (idempotência da sincronização offline — regra 8.3).
     * @param array<string,mixed> $registro
     * @return array{registro:array<string,mixed>, duplicado:bool}
     */
    public function criar(array $registro): array
    {
        $uuid = (string)($registro['uuid_cliente'] ?? '') ?: Identificadores::uuidV4();
        $existente = $this->buscarPorUuid($uuid);
        if ($existente !== null) {
            return ['registro' => $existente, 'duplicado' => true];
        }

        $this->executar(
            'INSERT INTO registros (codigo_publico, uuid_cliente, grupo_registro, familia_id, crianca_id,
                                    categoria_id, roteiro_bloco_id, usuario_id, inicio, fim, dados, observacao,
                                    status, justificativa, origem)
             VALUES (:codigo, :uuid, :grupo, :familia_id, :crianca, :categoria, :bloco, :usuario, :inicio, :fim,
                     :dados, :observacao, :status, :justificativa, :origem)',
            [
                'codigo'        => Identificadores::codigoPublico(),
                'uuid'          => $uuid,
                'grupo'         => $registro['grupo_registro'] ?? null,
                'crianca'       => (int)$registro['crianca_id'],
                'categoria'     => (int)$registro['categoria_id'],
                'bloco'         => $registro['roteiro_bloco_id'] ?? null,
                'usuario'       => (int)$registro['usuario_id'],
                'inicio'        => (string)$registro['inicio'],
                'fim'           => $registro['fim'] ?? null,
                'dados'         => $registro['dados'] === null ? null : json_encode($registro['dados'], JSON_UNESCAPED_UNICODE),
                'observacao'    => $registro['observacao'] ?? null,
                'status'        => (string)($registro['status'] ?? 'feito'),
                'justificativa' => $registro['justificativa'] ?? null,
                'origem'        => (string)($registro['origem'] ?? 'online'),
            ]
        );
        return ['registro' => $this->buscarPorUuid($uuid) ?? [], 'duplicado' => false];
    }

    /** @param array<string,mixed> $campos apenas os campos mutáveis */
    public function atualizar(int $id, array $campos): void
    {
        $this->executar(
            'UPDATE registros SET inicio = :inicio, fim = :fim, dados = :dados, observacao = :observacao,
                    status = :status, justificativa = :justificativa
              WHERE familia_id = :familia_id AND id = :id',
            [
                'id'            => $id,
                'inicio'        => (string)$campos['inicio'],
                'fim'           => $campos['fim'] ?? null,
                'dados'         => $campos['dados'] === null ? null : json_encode($campos['dados'], JSON_UNESCAPED_UNICODE),
                'observacao'    => $campos['observacao'] ?? null,
                'status'        => (string)$campos['status'],
                'justificativa' => $campos['justificativa'] ?? null,
            ]
        );
    }

    /** Exclusão LÓGICA com motivo obrigatório (regra 8.1). */
    public function excluirLogicamente(int $id, int $usuarioId, string $motivo): void
    {
        $this->executar(
            'UPDATE registros SET excluido_em = NOW(), excluido_por = :usuario, motivo_exclusao = :motivo
              WHERE familia_id = :familia_id AND id = :id AND excluido_em IS NULL',
            ['id' => $id, 'usuario' => $usuarioId, 'motivo' => $motivo]
        );
    }

    /** Momento do último registro criado na família (alerta de omissão). */
    public function ultimoRegistroDaFamilia(): ?string
    {
        $linha = $this->buscarUm(
            'SELECT MAX(criado_em) AS ultimo FROM registros WHERE familia_id = :familia_id'
        );
        return $linha['ultimo'] ?? null;
    }

    /** Contagens do dia por grupo/slug de categoria (faixa de status dos pais). */
    public function estatisticasDoDia(int $criancaId, string $data): array
    {
        $linhas = $this->buscarTodos(
            'SELECT c.grupo, c.slug, COUNT(*) AS total
               FROM registros r JOIN categorias c ON c.id = r.categoria_id
              WHERE r.familia_id = :familia_id AND r.crianca_id = :crianca
                AND r.inicio >= :inicio AND r.inicio < :fim AND r.excluido_em IS NULL
              GROUP BY c.grupo, c.slug',
            ['crianca' => $criancaId, 'inicio' => $data . ' 00:00:00', 'fim' => $data . ' 23:59:59']
        );
        $porSlug = [];
        foreach ($linhas as $linha) {
            $porSlug[$linha['slug']] = (int)$linha['total'];
        }
        return [
            'mamadas' => ($porSlug['amamentacao'] ?? 0) + ($porSlug['mamadeira'] ?? 0),
            'sonecas' => ($porSlug['soneca'] ?? 0) + ($porSlug['sono-noturno'] ?? 0),
            'fraldas' => $porSlug['fralda'] ?? 0,
            'por_slug' => $porSlug,
        ];
    }
}
