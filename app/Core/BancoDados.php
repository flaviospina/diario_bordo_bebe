<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Conexão PDO única (MySQL 8 / MariaDB) com prepared statements obrigatórios.
 */
final class BancoDados
{
    private static ?PDO $conexao = null;

    public static function conexao(): PDO
    {
        if (self::$conexao === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Ambiente::obter('BD_HOST', 'localhost'),
                Ambiente::obter('BD_PORTA', '3306'),
                Ambiente::obter('BD_NOME')
            );
            self::$conexao = new PDO(
                $dsn,
                Ambiente::obter('BD_USUARIO'),
                Ambiente::obter('BD_SENHA'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Prepares nativos: nada de emulação, prepared statements de verdade
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // Fuso do MySQL alinhado ao da aplicação (America/Sao_Paulo);
            // calculado do fuso PHP para não depender das tabelas de timezone do MySQL
            $deslocamento = (new \DateTime('now', new \DateTimeZone(date_default_timezone_get())))->format('P');
            self::$conexao->exec("SET time_zone = " . self::$conexao->quote($deslocamento));
        }
        return self::$conexao;
    }
}
