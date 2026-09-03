<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Identificadores;

/**
 * Novidades da plataforma: comunicados de novas funcionalidades, com página
 * pública de detalhes (/novidades) e e-mail macro aos responsáveis.
 */
final class RepositorioNovidades extends RepositorioSistema
{
    public function criar(string $titulo, string $resumo, string $detalhes, int $criadoPor): int
    {
        $base = Identificadores::slug($titulo);
        $slug = $base;
        $sufixo = 2;
        while ($this->buscarUm('SELECT id FROM novidades WHERE slug = :slug LIMIT 1', ['slug' => $slug]) !== null) {
            $slug = $base . '-' . $sufixo;
            $sufixo++;
        }
        $this->executar(
            'INSERT INTO novidades (slug, titulo, resumo, detalhes, criado_por)
             VALUES (:slug, :titulo, :resumo, :detalhes, :autor)',
            ['slug' => $slug, 'titulo' => $titulo, 'resumo' => $resumo, 'detalhes' => $detalhes, 'autor' => $criadoPor]
        );
        return (int)$this->bd->lastInsertId();
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM novidades WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return array<int,array<string,mixed>> publicadas, para a página pública */
    public function listarPublicadas(): array
    {
        return $this->executar(
            'SELECT * FROM novidades WHERE publicado = 1 ORDER BY criado_em DESC'
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> todas, para o painel */
    public function listarTodas(): array
    {
        return $this->executar('SELECT * FROM novidades ORDER BY criado_em DESC')->fetchAll();
    }

    public function marcarEmailEnviado(int $id): void
    {
        $this->executar('UPDATE novidades SET email_enviado_em = NOW() WHERE id = :id', ['id' => $id]);
    }

    /**
     * Destinatários do e-mail macro: responsáveis ativos de famílias ativas.
     * @return array<int,array{nome:string,email:string,familia_id:int}>
     */
    public function destinatarios(): array
    {
        return $this->executar(
            "SELECT u.nome, u.email, u.familia_id
               FROM usuarios u
               JOIN familias f ON f.id = u.familia_id
              WHERE u.ativo = 1 AND u.papel IN ('admin_familia', 'responsavel')
                AND f.status = 'ativa' AND f.plano <> 'plataforma'
              ORDER BY u.id"
        )->fetchAll();
    }

    public function alternarPublicado(int $id): void
    {
        $this->executar('UPDATE novidades SET publicado = 1 - publicado WHERE id = :id', ['id' => $id]);
    }
}
