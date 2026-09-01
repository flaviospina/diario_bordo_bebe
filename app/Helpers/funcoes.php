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

/**
 * Curva de crescimento em SVG (Alteração 01): referência OMS P3/P50/P97 em
 * tom único de sálvia + pontos medidos da criança. Sem cor de alarme — quem
 * interpreta é o pediatra. Usada na ficha essencial e na ficha pública.
 * @param array{unidade:string,referencia:array,pontos:array} $curva
 *        série gerada por App\Services\ServicoCrescimento::curvas()
 */
function grafico_crescimento(string $rotulo, array $curva): string
{
    $referencia = $curva['referencia'];
    $pontos = $curva['pontos'];
    $mesMaximo = max(1, (int)end($referencia)[0]);

    $valores = [];
    foreach ($referencia as [$mes, $p3, , $p97]) {
        if ($p3 !== null) { $valores[] = $p3; }
        if ($p97 !== null) { $valores[] = $p97; }
    }
    foreach ($pontos as [, $valor]) { $valores[] = $valor; }
    $minimo = floor(min($valores) * 0.97);
    $maximo = ceil(max($valores) * 1.03);
    $faixa = max(0.001, $maximo - $minimo);

    $x = static fn(float $mes): float => round(36 + ($mes / $mesMaximo) * (312 - 36), 1);
    $y = static fn(float $valor): float => round(158 - (($valor - $minimo) / $faixa) * (158 - 12), 1);

    $linha = static function (int $indice) use ($referencia, $x, $y): string {
        $partes = [];
        foreach ($referencia as $item) {
            if ($item[$indice] !== null) {
                $partes[] = $x((float)$item[0]) . ',' . $y((float)$item[$indice]);
            }
        }
        return implode(' ', $partes);
    };
    $passoMes = $mesMaximo <= 12 ? 3 : ($mesMaximo <= 30 ? 6 : 12);

    $svg = '';
    for ($mes = 0; $mes <= $mesMaximo; $mes += $passoMes) {
        $svg .= '<line x1="' . $x((float)$mes) . '" y1="12" x2="' . $x((float)$mes) . '" y2="158" class="curva-grade"></line>';
        $svg .= '<text x="' . $x((float)$mes) . '" y="172" class="curva-texto" text-anchor="middle">' . $mes . 'm</text>';
    }
    foreach ([$minimo, $minimo + $faixa / 2, $maximo] as $marca) {
        $svg .= '<text x="30" y="' . ($y((float)$marca) + 3) . '" class="curva-texto" text-anchor="end">'
            . e(number_format((float)$marca, $faixa < 8 ? 1 : 0, ',', '')) . '</text>';
    }
    $svg .= '<polyline points="' . $linha(1) . '" class="curva-referencia"></polyline>';
    $svg .= '<polyline points="' . $linha(2) . '" class="curva-referencia curva-mediana"></polyline>';
    $svg .= '<polyline points="' . $linha(3) . '" class="curva-referencia"></polyline>';
    $ultimoItem = end($referencia);
    foreach ([1 => 'P3', 2 => 'P50', 3 => 'P97'] as $indice => $nomePercentil) {
        $svg .= '<text x="316" y="' . ($y((float)$ultimoItem[$indice]) + 3) . '" class="curva-texto">' . $nomePercentil . '</text>';
    }
    if (count($pontos) > 1) {
        $partes = [];
        foreach ($pontos as [$mes, $valor]) { $partes[] = $x((float)$mes) . ',' . $y((float)$valor); }
        $svg .= '<polyline points="' . implode(' ', $partes) . '" class="curva-crianca"></polyline>';
    }
    foreach ($pontos as [$mes, $valor]) {
        $svg .= '<circle cx="' . $x((float)$mes) . '" cy="' . $y((float)$valor) . '" r="3.4" class="curva-ponto"></circle>';
    }

    return '<figure class="grafico-crescimento">'
        . '<figcaption>' . e($rotulo) . ' <small class="texto-apoio">(' . e($curva['unidade'])
        . ') · referência OMS P3–P97</small></figcaption>'
        . '<svg viewBox="0 0 332 178" role="img" aria-label="Curva de ' . e($rotulo) . '">' . $svg . '</svg>'
        . '</figure>';
}
