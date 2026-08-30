<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var string $data */
/** @var string $texto */
/** @var array $dia */

$estatisticas = $dia['estatisticas'];
?>
<h2>Resumo do dia — <?= e(data_br($data . ' 0:0', 'd/m/Y')) ?></h2>

<div class="faixa-status">
    <span class="pilula">🍼 <?= (int)$estatisticas['mamadas'] ?> mamadas</span>
    <span class="pilula">😴 <?= (int)$estatisticas['sonecas'] ?> sonecas</span>
    <span class="pilula">🧷 <?= (int)$estatisticas['fraldas'] ?> fraldas</span>
</div>

<div class="cartao area-impressao">
    <p style="white-space: pre-line"><?= e($texto) ?></p>
</div>

<div class="linha-campos nao-imprimir">
    <form method="post" action="<?= e(url('relatorios.exportar')) ?>" class="form-inline">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <input type="hidden" name="tipo" value="pdf_resumo">
        <input type="hidden" name="data" value="<?= e($data) ?>">
        <button type="submit" class="botao botao-primario">Baixar PDF</button>
    </form>
    <a class="botao botao-contorno" href="<?= e(url('pais.acompanhar.data', ['data' => $data])) ?>">Ver a grade do dia</a>
</div>
