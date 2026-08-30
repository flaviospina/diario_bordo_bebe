<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var int $periodo */
/** @var array $dias */

$maximos = ['sono_min' => 1, 'mamadas' => 1, 'volume_ml' => 1, 'fraldas' => 1];
foreach ($dias as $valores) {
    foreach ($maximos as $chave => $atual) {
        $maximos[$chave] = max($atual, (int)$valores[$chave]);
    }
}
$metricas = [
    'sono_min' => ['rotulo' => 'Sono total (h/dia)', 'formato' => static fn(int $v): string => number_format($v / 60, 1, ',', '')],
    'mamadas' => ['rotulo' => 'Mamadas por dia', 'formato' => static fn(int $v): string => (string)$v],
    'volume_ml' => ['rotulo' => 'Volume ingerido (ml/dia)', 'formato' => static fn(int $v): string => (string)$v],
    'fraldas' => ['rotulo' => 'Fraldas por dia', 'formato' => static fn(int $v): string => (string)$v],
];
?>
<h2>Relatórios — <?= e($crianca['apelido'] ?: $crianca['nome']) ?></h2>

<div class="barra-dia">
    <div class="navega-dia">
        <a class="botao botao-pequeno <?= $periodo === 7 ? 'botao-primario' : 'botao-contorno' ?>"
           href="<?= e(url('relatorios.index')) ?>?periodo=7">7 dias</a>
        <a class="botao botao-pequeno <?= $periodo === 30 ? 'botao-primario' : 'botao-contorno' ?>"
           href="<?= e(url('relatorios.index')) ?>?periodo=30">30 dias</a>
        <a class="botao botao-pequeno botao-contorno" href="<?= e(url('relatorios.pediatra')) ?>">🩺 Modo Pediatra</a>
        <a class="botao botao-pequeno botao-contorno" href="<?= e(url('relatorios.resumo', ['data' => hoje()])) ?>">Resumo de hoje</a>
    </div>
    <?php if (count($criancas) > 1): ?>
        <form method="get" action="<?= e(url('relatorios.index')) ?>" class="form-inline">
            <input type="hidden" name="periodo" value="<?= (int)$periodo ?>">
            <select name="crianca" onchange="this.form.submit()" aria-label="Criança">
                <?php foreach ($criancas as $opcao): ?>
                    <option value="<?= e($opcao['slug']) ?>" <?= $opcao['slug'] === $crianca['slug'] ? 'selected' : '' ?>>
                        <?= e($opcao['apelido'] ?: $opcao['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php foreach ($metricas as $chave => $metrica): ?>
    <div class="cartao">
        <h3><?= e($metrica['rotulo']) ?></h3>
        <div class="grafico-barras" role="img" aria-label="<?= e($metrica['rotulo']) ?> nos últimos <?= (int)$periodo ?> dias">
            <?php foreach ($dias as $dia => $valores): ?>
                <?php
                $valor = (int)$valores[$chave];
                $altura = $maximos[$chave] > 0 ? max(2, (int)round(($valor / $maximos[$chave]) * 100)) : 2;
                ?>
                <div class="coluna-barra" title="<?= e(data_br($dia . ' 0:0', 'd/m')) ?>: <?= e($metrica['formato']($valor)) ?>">
                    <span class="valor-barra"><?= $valor > 0 ? e($metrica['formato']($valor)) : '' ?></span>
                    <div class="barra" style="height: <?= $altura ?>%"></div>
                    <span class="rotulo-barra"><?= e(data_br($dia . ' 0:0', $periodo === 7 ? 'd/m' : 'd')) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="cartao">
    <h3>Exportar</h3>
    <form method="post" action="<?= e(url('relatorios.exportar')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <input type="hidden" name="tipo" value="csv">
        <div class="linha-campos">
            <div>
                <label for="de">De</label>
                <input type="date" id="de" name="de" value="<?= e(date('Y-m-d', strtotime('-29 days'))) ?>">
            </div>
            <div>
                <label for="ate">Até</label>
                <input type="date" id="ate" name="ate" value="<?= e(hoje()) ?>">
            </div>
        </div>
        <button type="submit" class="botao botao-primario">Baixar CSV do período</button>
    </form>
</div>
