<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Lista de espera da landing pública ("quero um convite").
 * Reenvio do mesmo e-mail atualiza a linha existente — sem duplicar lead.
 */
final class RepositorioListaEspera extends RepositorioSistema
{
    /** @return bool true se é um lead novo, false se atualizou um existente */
    public function inscrever(string $nome, string $email, ?string $whatsapp, ?string $mensagem, string $ip): bool
    {
        $existente = $this->buscarUm(
            'SELECT id FROM lista_espera WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
        if ($existente !== null) {
            $this->executar(
                'UPDATE lista_espera SET nome = :nome, whatsapp = :whatsapp, mensagem = :mensagem
                  WHERE id = :id',
                ['nome' => $nome, 'whatsapp' => $whatsapp, 'mensagem' => $mensagem, 'id' => (int)$existente['id']]
            );
            return false;
        }
        $this->executar(
            'INSERT INTO lista_espera (nome, email, whatsapp, mensagem, ip)
             VALUES (:nome, :email, :whatsapp, :mensagem, :ip)',
            ['nome' => $nome, 'email' => $email, 'whatsapp' => $whatsapp, 'mensagem' => $mensagem, 'ip' => $ip]
        );
        return true;
    }

    public function buscar(int $id): ?array
    {
        return $this->buscarUm('SELECT * FROM lista_espera WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 100): array
    {
        return $this->executar(
            "SELECT * FROM lista_espera
              ORDER BY FIELD(status, 'novo', 'convidado', 'descartado'), criado_em DESC
              LIMIT " . max(1, min(500, $limite))
        )->fetchAll();
    }

    public function mudarStatus(int $id, string $status): void
    {
        if (!in_array($status, ['novo', 'convidado', 'descartado'], true)) {
            return;
        }
        $this->executar(
            'UPDATE lista_espera SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );
    }

    public function contarNovos(): int
    {
        return (int)$this->executar("SELECT COUNT(*) FROM lista_espera WHERE status = 'novo'")->fetchColumn();
    }
}
