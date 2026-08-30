<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

final class RepositorioCriancas extends RepositorioBase
{
    /** @return array<int,array<string,mixed>> */
    public function listar(bool $somenteAtivas = true): array
    {
        $filtro = $somenteAtivas ? 'AND ativo = 1' : '';
        return $this->buscarTodos(
            "SELECT * FROM criancas WHERE familia_id = :familia_id {$filtro} ORDER BY nome"
        );
    }

    public function buscarPorSlug(string $slug): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM criancas WHERE familia_id = :familia_id AND slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->buscarUm(
            'SELECT * FROM criancas WHERE familia_id = :familia_id AND id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** @param array<string,mixed> $dados campos opcionais já validados */
    public function criar(string $nome, array $dados): int
    {
        $this->executar(
            'INSERT INTO criancas (familia_id, codigo_publico, slug, nome, apelido, data_nascimento,
                                   sexo, tipo_sanguineo, alergias, condicoes_saude, medicacoes_continuas,
                                   pediatra_nome, pediatra_telefone)
             VALUES (:familia_id, :codigo, :slug, :nome, :apelido, :nascimento, :sexo, :sanguineo,
                     :alergias, :condicoes, :medicacoes, :pediatra_nome, :pediatra_telefone)',
            [
                'codigo'            => Identificadores::codigoPublico(),
                'slug'              => $this->slugDisponivel($nome),
                'nome'              => $nome,
                'apelido'           => $dados['apelido'] ?? null,
                'nascimento'        => $dados['data_nascimento'] ?? null,
                'sexo'              => $dados['sexo'] ?? null,
                'sanguineo'         => $dados['tipo_sanguineo'] ?? null,
                'alergias'          => $dados['alergias'] ?? null,
                'condicoes'         => $dados['condicoes_saude'] ?? null,
                'medicacoes'        => $dados['medicacoes_continuas'] ?? null,
                'pediatra_nome'     => $dados['pediatra_nome'] ?? null,
                'pediatra_telefone' => $dados['pediatra_telefone'] ?? null,
            ]
        );
        return $this->ultimoId();
    }

    /** @param array<string,mixed> $dados */
    public function atualizar(int $id, array $dados): void
    {
        $this->executar(
            'UPDATE criancas SET nome = :nome, apelido = :apelido, data_nascimento = :nascimento,
                    sexo = :sexo, tipo_sanguineo = :sanguineo, alergias = :alergias,
                    condicoes_saude = :condicoes, medicacoes_continuas = :medicacoes,
                    pediatra_nome = :pediatra_nome, pediatra_telefone = :pediatra_telefone,
                    ativo = :ativo
              WHERE familia_id = :familia_id AND id = :id',
            [
                'id'                => $id,
                'nome'              => $dados['nome'],
                'apelido'           => $dados['apelido'] ?? null,
                'nascimento'        => $dados['data_nascimento'] ?? null,
                'sexo'              => $dados['sexo'] ?? null,
                'sanguineo'         => $dados['tipo_sanguineo'] ?? null,
                'alergias'          => $dados['alergias'] ?? null,
                'condicoes'         => $dados['condicoes_saude'] ?? null,
                'medicacoes'        => $dados['medicacoes_continuas'] ?? null,
                'pediatra_nome'     => $dados['pediatra_nome'] ?? null,
                'pediatra_telefone' => $dados['pediatra_telefone'] ?? null,
                'ativo'             => (int)($dados['ativo'] ?? 1),
            ]
        );
    }

    public function atualizarFoto(int $id, ?string $caminho): void
    {
        $this->executar(
            'UPDATE criancas SET foto_path = :foto WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id, 'foto' => $caminho]
        );
    }

    /** Slug único dentro da família; colisão ganha sufixo -2, -3... */
    private function slugDisponivel(string $nome): string
    {
        $base = Identificadores::slug($nome);
        $slug = $base;
        $sufixo = 2;
        while ($this->buscarPorSlug($slug) !== null) {
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
        return $slug;
    }
}
