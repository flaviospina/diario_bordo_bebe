<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioPlanos;

/**
 * Ponto ÚNICO de consulta a limites de plano. O código nunca conhece números:
 * pergunta aqui, e a resposta vem do JSON `limites` do plano da família
 * (null = ilimitado). Mudar um plano é mudar a tabela, não o código.
 *
 * Plano desconhecido ou família de plataforma → sem limites (fail-open:
 * um catálogo mal configurado nunca pode trancar uma família fora).
 */
final class ServicoPlano
{
    /** @var array<int,array<string,mixed>> cache por requisição */
    private static array $cache = [];

    public function limites(?int $familiaId = null): array
    {
        $familiaId ??= Autenticacao::familiaId();
        if (isset(self::$cache[$familiaId])) {
            return self::$cache[$familiaId];
        }
        $familia = (new RepositorioFamilias())->buscarPorId($familiaId);
        $plano = $familia !== null ? (new RepositorioPlanos())->porChave((string)$familia['plano']) : null;
        $limites = $plano !== null ? (json_decode((string)($plano['limites'] ?? 'null'), true) ?: []) : [];
        return self::$cache[$familiaId] = $limites;
    }

    /** Recursos liga/desliga (fotos, relatorios_pdf, ficha_pediatra...). Ausente = liberado. */
    public function permite(string $recurso, ?int $familiaId = null): bool
    {
        $limites = $this->limites($familiaId);
        return !array_key_exists($recurso, $limites) || $limites[$recurso] !== false;
    }

    /** Limite numérico (max_criancas, max_usuarios...). null/ausente = ilimitado. */
    public function limite(string $chave, ?int $familiaId = null): ?int
    {
        $valor = $this->limites($familiaId)[$chave] ?? null;
        return is_numeric($valor) ? (int)$valor : null;
    }

    /** true se ainda cabe mais um (dado o total atual). */
    public function aindaCabe(string $chave, int $totalAtual, ?int $familiaId = null): bool
    {
        $maximo = $this->limite($chave, $familiaId);
        return $maximo === null || $totalAtual < $maximo;
    }
}
