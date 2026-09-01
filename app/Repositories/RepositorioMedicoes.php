<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Medições de crescimento. O "último peso" é SEMPRE a linha mais recente
 * daqui — nunca um campo sobrescrito em criancas (regra da Alteração 01):
 * sobrescrever destruiria a curva de crescimento. Correção não apaga nem
 * edita: gera nova linha (a antiga permanece no histórico).
 */
final class RepositorioMedicoes extends RepositorioBase
{
    /** @param array<string,mixed> $medicao */
    public function criar(array $medicao): int
    {
        $this->executar(
            'INSERT INTO medicoes (familia_id, crianca_id, medido_em, peso_g, altura_mm,
                                   perimetro_cefalico_mm, origem, registrado_por_usuario_id,
                                   profissional_id, profissional_nome_livre,
                                   percentil_peso, percentil_altura, percentil_pc,
                                   escore_z_peso, escore_z_altura, escore_z_pc,
                                   observacao, status)
             VALUES (:familia_id, :crianca, :medido_em, :peso, :altura, :pc, :origem, :usuario,
                     :profissional, :profissional_nome, :p_peso, :p_altura, :p_pc,
                     :z_peso, :z_altura, :z_pc, :observacao, :status)',
            [
                'crianca'           => (int)$medicao['crianca_id'],
                'medido_em'         => (string)$medicao['medido_em'],
                'peso'              => $medicao['peso_g'],
                'altura'            => $medicao['altura_mm'],
                'pc'                => $medicao['perimetro_cefalico_mm'],
                'origem'            => (string)$medicao['origem'],
                'usuario'           => $medicao['registrado_por_usuario_id'] ?? null,
                'profissional'      => $medicao['profissional_id'] ?? null,
                'profissional_nome' => $medicao['profissional_nome_livre'] ?? null,
                'p_peso'            => $medicao['percentil_peso'] ?? null,
                'p_altura'          => $medicao['percentil_altura'] ?? null,
                'p_pc'              => $medicao['percentil_pc'] ?? null,
                'z_peso'            => $medicao['escore_z_peso'] ?? null,
                'z_altura'          => $medicao['escore_z_altura'] ?? null,
                'z_pc'              => $medicao['escore_z_pc'] ?? null,
                'observacao'        => $medicao['observacao'] ?? null,
                'status'            => (string)($medicao['status'] ?? 'confirmada'),
            ]
        );
        return $this->ultimoId();
    }

    /** @return array<int,array<string,mixed>> histórico completo, mais recente primeiro */
    public function listar(int $criancaId, int $limite = 200): array
    {
        return $this->buscarTodos(
            'SELECT m.*, p.nome AS profissional_nome, p.conselho_sigla, p.conselho_numero, p.conselho_uf
               FROM medicoes m
               LEFT JOIN profissionais p ON p.id = m.profissional_id
              WHERE m.familia_id = :familia_id AND m.crianca_id = :crianca
              ORDER BY m.medido_em DESC, m.id DESC
              LIMIT ' . max(1, min(1000, $limite)),
            ['crianca' => $criancaId]
        );
    }

    /** Último valor confirmado de cada medida (podem vir de linhas diferentes). */
    public function ultimasMedidas(int $criancaId): array
    {
        $ultimas = ['peso' => null, 'altura' => null, 'pc' => null];
        foreach ([['peso', 'peso_g', 'percentil_peso'], ['altura', 'altura_mm', 'percentil_altura'], ['pc', 'perimetro_cefalico_mm', 'percentil_pc']] as [$chave, $coluna, $percentil]) {
            $ultimas[$chave] = $this->buscarUm(
                "SELECT medido_em, {$coluna} AS valor, {$percentil} AS percentil, origem
                   FROM medicoes
                  WHERE familia_id = :familia_id AND crianca_id = :crianca
                    AND status = 'confirmada' AND {$coluna} IS NOT NULL
                  ORDER BY medido_em DESC, id DESC LIMIT 1",
                ['crianca' => $criancaId]
            );
        }
        return $ultimas;
    }

    /** @return array<int,array<string,mixed>> pendentes de confirmação dos pais */
    public function pendentes(?int $criancaId = null): array
    {
        $filtro = $criancaId !== null ? 'AND m.crianca_id = :crianca' : '';
        $parametros = $criancaId !== null ? ['crianca' => $criancaId] : [];
        return $this->buscarTodos(
            "SELECT m.*, c.nome AS crianca_nome, c.slug AS crianca_slug,
                    p.nome AS profissional_nome
               FROM medicoes m
               JOIN criancas c ON c.id = m.crianca_id
               LEFT JOIN profissionais p ON p.id = m.profissional_id
              WHERE m.familia_id = :familia_id AND m.status = 'pendente' {$filtro}
              ORDER BY m.criado_em DESC",
            $parametros
        );
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM medicoes WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function confirmar(int $id, ?int $usuarioId): void
    {
        $this->executar(
            "UPDATE medicoes SET status = 'confirmada', confirmado_por = :usuario, confirmado_em = NOW()
              WHERE familia_id = :familia_id AND id = :id AND status = 'pendente'",
            ['id' => $id, 'usuario' => $usuarioId]
        );
    }
}
