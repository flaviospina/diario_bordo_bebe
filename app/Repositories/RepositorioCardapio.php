<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Cardápio semanal + lista de compras (Rodada 6). Tudo filtrado por família.
 */
final class RepositorioCardapio extends RepositorioBase
{
    // ── Semanas ───────────────────────────────────────────────

    public function criarSemana(int $criancaId, string $titulo, string $inicio, string $fim, string $origem, ?string $descricao, ?int $criadoPor): int
    {
        $this->executar(
            'INSERT INTO cardapios_semanas (familia_id, crianca_id, titulo, inicio, fim, origem, descricao, criado_por)
             VALUES (:familia_id, :crianca, :titulo, :inicio, :fim, :origem, :descricao, :criado_por)',
            ['crianca' => $criancaId, 'titulo' => mb_substr($titulo, 0, 120), 'inicio' => $inicio, 'fim' => $fim,
             'origem' => $origem, 'descricao' => $descricao !== null ? mb_substr($descricao, 0, 255) : null, 'criado_por' => $criadoPor]
        );
        return $this->ultimoId();
    }

    public function buscarSemana(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM cardapios_semanas WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function semanaPorInicio(int $criancaId, string $inicio): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM cardapios_semanas WHERE familia_id = :familia_id AND crianca_id = :crianca AND inicio = :inicio LIMIT 1',
            ['crianca' => $criancaId, 'inicio' => $inicio]
        );
    }

    /** Semana que contém a data (ou null). */
    public function semanaDaData(int $criancaId, string $data): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM cardapios_semanas
              WHERE familia_id = :familia_id AND crianca_id = :crianca AND :data BETWEEN inicio AND fim
              ORDER BY inicio DESC LIMIT 1',
            ['crianca' => $criancaId, 'data' => $data]
        );
    }

    /** @return array<int,array<string,mixed>> mais recentes primeiro, com progresso */
    public function listarSemanas(int $criancaId): array
    {
        return $this->buscarTodos(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM compras_itens i WHERE i.semana_id = s.id) AS total_itens,
                    (SELECT COUNT(*) FROM compras_itens i WHERE i.semana_id = s.id AND i.comprado = 1) AS comprados
               FROM cardapios_semanas s
              WHERE s.familia_id = :familia_id AND s.crianca_id = :crianca
              ORDER BY s.inicio DESC',
            ['crianca' => $criancaId]
        );
    }

    public function tocarSemana(int $semanaId): void
    {
        $this->executar(
            'UPDATE cardapios_semanas SET atualizado_em = NOW() WHERE familia_id = :familia_id AND id = :id',
            ['id' => $semanaId]
        );
    }

    // ── Dias ──────────────────────────────────────────────────

    public function salvarDia(int $semanaId, string $data, ?string $frutaManha, ?string $frutaTarde, ?string $almoco, ?string $jantar, array $almocoSlugs, array $jantarSlugs): void
    {
        $this->executar(
            'INSERT INTO cardapio_dias (semana_id, familia_id, data, fruta_manha, fruta_tarde, almoco, jantar, almoco_slugs, jantar_slugs)
             VALUES (:semana, :familia_id, :data, :fm, :ft, :almoco, :jantar, :as, :js)
             ON DUPLICATE KEY UPDATE fruta_manha = VALUES(fruta_manha), fruta_tarde = VALUES(fruta_tarde),
                 almoco = VALUES(almoco), jantar = VALUES(jantar), almoco_slugs = VALUES(almoco_slugs), jantar_slugs = VALUES(jantar_slugs)',
            [
                'semana' => $semanaId, 'data' => $data,
                'fm' => $frutaManha !== null ? mb_substr($frutaManha, 0, 160) : null,
                'ft' => $frutaTarde !== null ? mb_substr($frutaTarde, 0, 160) : null,
                'almoco' => $almoco !== null ? mb_substr($almoco, 0, 255) : null,
                'jantar' => $jantar !== null ? mb_substr($jantar, 0, 255) : null,
                'as' => json_encode(array_values($almocoSlugs)),
                'js' => json_encode(array_values($jantarSlugs)),
            ]
        );
    }

    /** @return array<int,array<string,mixed>> os dias da semana em ordem */
    /** Guarda o planejamento importado e os alertas do dia (não mexe no texto do cardápio). */
    public function salvarPlanejamentoDia(int $semanaId, string $data, array $planejado, array $alertas): void
    {
        $this->executar(
            'UPDATE cardapio_dias SET planejado_json = :p, alertas_json = :a
              WHERE familia_id = :familia_id AND semana_id = :semana AND data = :data',
            ['semana' => $semanaId, 'data' => $data, 'p' => json_encode($planejado, JSON_UNESCAPED_UNICODE),
             'a' => $alertas === [] ? null : json_encode($alertas, JSON_UNESCAPED_UNICODE)]
        );
    }

    /** Dias de um intervalo (qualquer semana) da criança. */
    public function diasNoIntervalo(int $criancaId, string $inicio, string $fim): array
    {
        return $this->buscarTodos(
            'SELECT d.*, s.titulo AS semana_titulo FROM cardapio_dias d JOIN cardapios_semanas s ON s.id = d.semana_id
              WHERE d.familia_id = :familia_id AND s.crianca_id = :crianca AND d.data BETWEEN :inicio AND :fim
              ORDER BY d.data',
            ['crianca' => $criancaId, 'inicio' => $inicio, 'fim' => $fim]
        );
    }

    public function diasDaSemana(int $semanaId): array
    {
        return $this->buscarTodos(
            'SELECT * FROM cardapio_dias WHERE familia_id = :familia_id AND semana_id = :semana ORDER BY data',
            ['semana' => $semanaId]
        );
    }

    public function diaDaData(int $criancaId, string $data): ?array
    {
        return $this->buscarUm(
            'SELECT d.*, s.titulo AS semana_titulo, s.id AS semana_id
               FROM cardapio_dias d JOIN cardapios_semanas s ON s.id = d.semana_id
              WHERE d.familia_id = :familia_id AND s.crianca_id = :crianca AND d.data = :data
              ORDER BY s.inicio DESC LIMIT 1',
            ['crianca' => $criancaId, 'data' => $data]
        );
    }

    // ── Itens de compra ───────────────────────────────────────

    public function adicionarItem(int $semanaId, string $stableId, string $categoria, string $nome, ?string $quantidade, ?string $nota, int $ordem): void
    {
        $this->executar(
            'INSERT INTO compras_itens (semana_id, familia_id, stable_id, categoria, nome, quantidade, nota, ordem)
             VALUES (:semana, :familia_id, :stable, :categoria, :nome, :quantidade, :nota, :ordem)
             ON DUPLICATE KEY UPDATE categoria = VALUES(categoria), nome = VALUES(nome),
                 quantidade = VALUES(quantidade), nota = VALUES(nota), ordem = VALUES(ordem)',
            ['semana' => $semanaId, 'stable' => mb_substr($stableId, 0, 40), 'categoria' => mb_substr($categoria, 0, 60),
             'nome' => mb_substr($nome, 0, 120), 'quantidade' => $quantidade !== null ? mb_substr($quantidade, 0, 120) : null,
             'nota' => $nota !== null ? mb_substr($nota, 0, 255) : null, 'ordem' => $ordem]
        );
    }

    /** @return array<int,array<string,mixed>> na ordem de exibição */
    public function itensDaSemana(int $semanaId): array
    {
        return $this->buscarTodos(
            'SELECT i.*, u.nome AS comprado_por_nome
               FROM compras_itens i LEFT JOIN usuarios u ON u.id = i.comprado_por
              WHERE i.familia_id = :familia_id AND i.semana_id = :semana
              ORDER BY i.ordem, i.id',
            ['semana' => $semanaId]
        );
    }

    public function buscarItem(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM compras_itens WHERE familia_id = :familia_id AND id = :id LIMIT 1', ['id' => $id]);
    }

    public function marcarItem(int $id, bool $comprado, int $usuarioId): void
    {
        $this->executar(
            'UPDATE compras_itens
                SET comprado = :comprado, comprado_em = IF(:comprado2 = 1, NOW(), NULL), comprado_por = IF(:comprado3 = 1, :usuario, NULL)
              WHERE familia_id = :familia_id AND id = :id',
            ['comprado' => $comprado ? 1 : 0, 'comprado2' => $comprado ? 1 : 0, 'comprado3' => $comprado ? 1 : 0,
             'usuario' => $usuarioId, 'id' => $id]
        );
    }

    public function marcarPorStableId(int $semanaId, string $stableId, bool $comprado, int $usuarioId): int
    {
        return $this->executar(
            'UPDATE compras_itens
                SET comprado = :comprado, comprado_em = IF(:comprado2 = 1, NOW(), NULL), comprado_por = IF(:comprado3 = 1, :usuario, NULL)
              WHERE familia_id = :familia_id AND semana_id = :semana AND stable_id = :stable',
            ['comprado' => $comprado ? 1 : 0, 'comprado2' => $comprado ? 1 : 0, 'comprado3' => $comprado ? 1 : 0,
             'usuario' => $usuarioId, 'semana' => $semanaId, 'stable' => $stableId]
        )->rowCount();
    }

    public function desmarcarTodos(int $semanaId): void
    {
        $this->executar(
            'UPDATE compras_itens SET comprado = 0, comprado_em = NULL, comprado_por = NULL
              WHERE familia_id = :familia_id AND semana_id = :semana',
            ['semana' => $semanaId]
        );
    }

    public function removerItem(int $id): void
    {
        $this->executar('DELETE FROM compras_itens WHERE familia_id = :familia_id AND id = :id', ['id' => $id]);
    }
}
