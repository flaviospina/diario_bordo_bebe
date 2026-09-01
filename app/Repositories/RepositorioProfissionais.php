<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Profissionais de saúde. Base "sistema" de propósito: a tabela é GLOBAL
 * (sem familia_id) — o mesmo pediatra atende crianças de famílias diferentes.
 * O profissional nunca consulta nada por aqui; só é referenciado pelas
 * medições/vacinas/consultas que registrou.
 */
final class RepositorioProfissionais extends RepositorioSistema
{
    public function buscarPorId(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM profissionais WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** Localiza pelo registro de conselho ou cria um novo. Retorna o id. */
    public function localizarOuCriar(string $nome, string $sigla, string $numero, string $uf): int
    {
        if ($sigla !== '' && $numero !== '') {
            $existente = $this->buscarUm(
                'SELECT id FROM profissionais
                  WHERE conselho_sigla = :sigla AND conselho_numero = :numero AND conselho_uf = :uf
                  LIMIT 1',
                ['sigla' => $sigla, 'numero' => $numero, 'uf' => $uf]
            );
            if ($existente !== null) {
                // Mantém o nome mais recente informado (sem apagar histórico de vínculos)
                $this->executar('UPDATE profissionais SET nome = :nome WHERE id = :id',
                    ['nome' => $nome, 'id' => (int)$existente['id']]);
                return (int)$existente['id'];
            }
        }
        $this->executar(
            "INSERT INTO profissionais (nome, tipo, conselho_sigla, conselho_numero, conselho_uf, verificado)
             VALUES (:nome, 'pediatra', :sigla, :numero, :uf, 0)",
            [
                'nome' => $nome,
                'sigla' => $sigla !== '' ? $sigla : null,
                'numero' => $numero !== '' ? $numero : null,
                'uf' => $uf !== '' ? $uf : null,
            ]
        );
        return (int)$this->bd->lastInsertId();
    }
}
