<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Rate limit do login: registra tentativas e conta falhas recentes
 * por e-mail e por IP.
 */
final class RepositorioTentativasLogin extends RepositorioSistema
{
    public function registrar(string $email, string $ip, bool $sucesso): void
    {
        $this->executar(
            'INSERT INTO tentativas_login (email, ip, sucesso) VALUES (:email, :ip, :sucesso)',
            ['email' => mb_strtolower($email), 'ip' => $ip, 'sucesso' => $sucesso ? 1 : 0]
        );
    }

    public function falhasRecentes(string $email, string $ip, int $janelaMinutos = 15): int
    {
        $linha = $this->buscarUm(
            'SELECT COUNT(*) AS total FROM tentativas_login
              WHERE sucesso = 0
                AND criado_em > DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
                AND (email = :email OR ip = :ip)',
            ['minutos' => $janelaMinutos, 'email' => mb_strtolower($email), 'ip' => $ip]
        );
        return (int)($linha['total'] ?? 0);
    }
}
