<?php

declare(strict_types=1);

/**
 * Helpers globais. Curto de propósito: só o que é usado em toda view/controller.
 */

use App\Core\Roteador;

/**
 * Gera o caminho de uma rota nomeada (único jeito permitido de criar links).
 * Ex.: url('registro.ver', ['codigo' => $registro['codigo_publico']])
 */
function url(string $nomeRota, array $parametros = []): string
{
    /** @var Roteador $roteador */
    $roteador = $GLOBALS['__roteador'];
    return $roteador->caminho($nomeRota, $parametros);
}

/**
 * URL absoluta de uma rota nomeada (links em e-mail e webhooks).
 * APP_URL já inclui o caminho base; como url() também o inclui, remove a
 * duplicação antes de concatenar.
 */
function url_absoluta(string $nomeRota, array $parametros = []): string
{
    $base = rtrim(\App\Core\Ambiente::obter('APP_URL'), '/');
    if (BASE_PATH !== '' && str_ends_with($base, BASE_PATH)) {
        $base = substr($base, 0, -strlen(BASE_PATH));
    }
    return $base . url($nomeRota, $parametros);
}

/** Escape de saída HTML — usar em TODA interpolação nas views. */
function e(?string $texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Caminho de asset estático com versão para cache-busting. */
function asset(string $caminho): string
{
    return BASE_PATH . '/assets/' . ltrim($caminho, '/') . '?v=' . APP_VERSAO;
}

/** Data/hora em formato brasileiro. */
function data_br(?string $dataHora, string $formato = 'd/m/Y H:i'): string
{
    if ($dataHora === null || $dataHora === '') {
        return '';
    }
    try {
        return (new DateTime($dataHora))->format($formato);
    } catch (Exception) {
        return '';
    }
}

/** Data de hoje no fuso da aplicação (America/Sao_Paulo), formato Y-m-d. */
function hoje(): string
{
    return (new DateTime('now'))->format('Y-m-d');
}

/** Agora no fuso da aplicação, formato de banco. */
function agora(): string
{
    return (new DateTime('now'))->format('Y-m-d H:i:s');
}
