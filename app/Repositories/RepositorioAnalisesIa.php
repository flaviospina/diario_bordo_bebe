<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Análises de acompanhamento por IA e as observações de cada uma.
 * Cada análise guarda o resumo estruturado que foi enviado (dados_base,
 * sem o nome da criança) — auditável a qualquer momento.
 */
final class RepositorioAnalisesIa extends RepositorioBase
{
    public function criarAnalise(
        int $criancaId,
        string $periodoDe,
        string $periodoAte,
        string $origem,
        ?string $modelo,
        array $dadosBase
    ): int {
        $this->executar(
            'INSERT INTO analises_ia (familia_id, crianca_id, periodo_de, periodo_ate, origem, modelo, dados_base)
             VALUES (:familia_id, :crianca, :de, :ate, :origem, :modelo, :dados)',
            [
                'crianca' => $criancaId,
                'de' => $periodoDe,
                'ate' => $periodoAte,
                'origem' => $origem,
                'modelo' => $modelo,
                'dados' => json_encode($dadosBase, JSON_UNESCAPED_UNICODE),
            ]
        );
        return $this->ultimoId();
    }

    public function adicionarObservacao(int $analiseId, int $criancaId, string $tipo, string $titulo, string $texto, int $ordem): void
    {
        $this->executar(
            'INSERT INTO observacoes_ia (analise_id, familia_id, crianca_id, tipo, titulo, texto, ordem)
             VALUES (:analise, :familia_id, :crianca, :tipo, :titulo, :texto, :ordem)',
            [
                'analise' => $analiseId,
                'crianca' => $criancaId,
                'tipo' => $tipo,
                'titulo' => mb_substr($titulo, 0, 160),
                'texto' => mb_substr($texto, 0, 2000),
                'ordem' => $ordem,
            ]
        );
    }

    /** @return array<int,array<string,mixed>> análises mais recentes, cada uma com 'observacoes' */
    public function listarComObservacoes(int $criancaId, int $limite = 8): array
    {
        $analises = $this->buscarTodos(
            'SELECT * FROM analises_ia
              WHERE familia_id = :familia_id AND crianca_id = :crianca
              ORDER BY gerado_em DESC, id DESC
              LIMIT ' . max(1, min(50, $limite)),
            ['crianca' => $criancaId]
        );
        foreach ($analises as &$analise) {
            $analise['observacoes'] = $this->buscarTodos(
                'SELECT * FROM observacoes_ia
                  WHERE familia_id = :familia_id AND analise_id = :analise
                  ORDER BY ordem, id',
                ['analise' => (int)$analise['id']]
            );
        }
        return $analises;
    }

    public function ultimaAnalise(int $criancaId): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM analises_ia
              WHERE familia_id = :familia_id AND crianca_id = :crianca
              ORDER BY gerado_em DESC, id DESC LIMIT 1',
            ['crianca' => $criancaId]
        );
    }
}
