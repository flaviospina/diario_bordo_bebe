<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Consentimentos LGPD. Escopo por usuário titular (não por família):
 * a babá é titular dos próprios dados, independentemente da família.
 */
final class RepositorioConsentimentos extends RepositorioSistema
{
    public function aceitouVersaoVigente(int $usuarioId, string $tipo): bool
    {
        $versao = LGPD_VERSOES_TERMOS[$tipo] ?? '1.0';
        return $this->buscarUm(
            'SELECT id FROM consentimentos_lgpd
              WHERE usuario_id = :usuario AND tipo = :tipo AND versao_termo = :versao
              LIMIT 1',
            ['usuario' => $usuarioId, 'tipo' => $tipo, 'versao' => $versao]
        ) !== null;
    }

    public function registrarAceite(int $usuarioId, string $tipo, string $ip): void
    {
        $versao = LGPD_VERSOES_TERMOS[$tipo] ?? '1.0';
        $this->executar(
            'INSERT INTO consentimentos_lgpd (usuario_id, tipo, versao_termo, ip)
             VALUES (:usuario, :tipo, :versao, :ip)',
            ['usuario' => $usuarioId, 'tipo' => $tipo, 'versao' => $versao, 'ip' => $ip]
        );
    }
}
