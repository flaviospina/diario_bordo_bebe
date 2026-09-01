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

    /** Foto da ficha essencial: caminho, thumb e código público de entrega. */
    public function atualizarFotoCrianca(int $id, ?string $caminho, ?string $thumb, ?string $codigo): void
    {
        $this->executar(
            'UPDATE criancas SET foto_path = :foto, foto_thumb = :thumb, foto_codigo = :codigo
              WHERE familia_id = :familia_id AND id = :id',
            ['id' => $id, 'foto' => $caminho, 'thumb' => $thumb, 'codigo' => $codigo]
        );
    }

    /** Entrega da foto por /foto/{codigo}, sempre restrita à família. */
    public function buscarPorFotoCodigo(string $codigo): ?array
    {
        return $this->buscarUm(
            'SELECT id, foto_path, foto_thumb FROM criancas
              WHERE familia_id = :familia_id AND foto_codigo = :codigo LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    /**
     * Campos da ficha essencial (Alteração 01). Update separado do fluxo
     * original de atualizar() para não tocar o contrato existente.
     * @param array<string,mixed> $dados
     */
    public function atualizarFichaEssencial(int $id, array $dados): void
    {
        $this->executar(
            'UPDATE criancas SET semanas_gestacao = :semanas, peso_nascimento_g = :peso,
                    comprimento_nascimento_mm = :comprimento,
                    perimetro_cefalico_nascimento_mm = :pc, tipo_parto = :parto,
                    convenio_nome = :convenio, convenio_carteirinha = :carteirinha,
                    hospital_referencia = :hospital, restricoes_alimentares = :restricoes
              WHERE familia_id = :familia_id AND id = :id',
            [
                'id'          => $id,
                'semanas'     => $dados['semanas_gestacao'] ?? null,
                'peso'        => $dados['peso_nascimento_g'] ?? null,
                'comprimento' => $dados['comprimento_nascimento_mm'] ?? null,
                'pc'          => $dados['perimetro_cefalico_nascimento_mm'] ?? null,
                'parto'       => $dados['tipo_parto'] ?? null,
                'convenio'    => $dados['convenio_nome'] ?? null,
                'carteirinha' => $dados['convenio_carteirinha'] ?? null,
                'hospital'    => $dados['hospital_referencia'] ?? null,
                'restricoes'  => $dados['restricoes_alimentares'] ?? null,
            ]
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
