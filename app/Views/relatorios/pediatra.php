<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var string $de */
/** @var string $ate */
/** @var array $dias */

$diasSemana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
$totais = ['sono_min' => 0, 'mamadas' => 0, 'volume_ml' => 0, 'fraldas' => 0, 'intercorrencias' => 0];
$diasComDado = 0;
foreach ($dias as $valores) {
    if (array_sum($valores) > 0) {
        $diasComDado++;
    }
    foreach ($totais as $chave => $atual) {
        $totais[$chave] += (int)$valores[$chave];
    }
}
$divisor = max(1, $diasComDado);
?>
<div class="area-impressao">
    <h2>Modo Pediatra — <?= e($crianca['nome']) ?></h2>
    <p class="texto-apoio">
        Período: <?= e(data_br($de . ' 0:0', 'd/m/Y')) ?> a <?= e(data_br($ate . ' 0:0', 'd/m/Y')) ?>
        <?= $crianca['data_nascimento'] !== null ? ' · Nascimento: ' . e(data_br((string)$crianca['data_nascimento'], 'd/m/Y')) : '' ?>
        <?= $crianca['alergias'] !== null ? ' · Alergias: ' . e((string)$crianca['alergias']) : '' ?>
    </p>

    <div class="tabela-rolavel"><table class="tabela tabela-compacta">
        <thead>
        <tr><th>Dia</th><th>Sono (h)</th><th>Mamadas</th><th>Volume (ml)</th><th>Fraldas</th><th>Intercorrências</th></tr>
        </thead>
        <tbody>
        <?php foreach ($dias as $dia => $valores): $temDado = array_sum($valores) > 0; ?>
            <tr>
                <td><?= e(data_br($dia . ' 0:0', 'd/m')) ?> <span class="texto-apoio"><?= e($diasSemana[(int)date('w', (int)strtotime($dia))]) ?></span></td>
                <td><?= $temDado ? e(number_format($valores['sono_min'] / 60, 1, ',', '')) : '—' ?></td>
                <td><?= $temDado ? (int)$valores['mamadas'] : '—' ?></td>
                <td><?= $temDado ? (int)$valores['volume_ml'] : '—' ?></td>
                <td><?= $temDado ? (int)$valores['fraldas'] . ($valores['coco'] > 0 ? ' (' . (int)$valores['coco'] . ' c/ cocô)' : '') : '—' ?></td>
                <td><?= (int)$valores['intercorrencias'] ?: '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th>Média/dia</th>
            <th><?= e(number_format($totais['sono_min'] / 60 / $divisor, 1, ',', '')) ?></th>
            <th><?= e(number_format($totais['mamadas'] / $divisor, 1, ',', '')) ?></th>
            <th><?= (int)round($totais['volume_ml'] / $divisor) ?></th>
            <th><?= e(number_format($totais['fraldas'] / $divisor, 1, ',', '')) ?></th>
            <th>total <?= (int)$totais['intercorrencias'] ?></th>
        </tr>
        </tfoot>
    </table></div>
</div>

<div class="linha-campos nao-imprimir" style="margin-top:1rem">
    <form method="post" action="<?= e(url('relatorios.exportar')) ?>" class="form-inline">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <input type="hidden" name="tipo" value="pdf_pediatra">
        <button type="submit" class="botao botao-primario">Baixar PDF (uma página)</button>
    </form>
    <button type="button" class="botao botao-contorno" onclick="window.print()">Imprimir</button>
</div>
