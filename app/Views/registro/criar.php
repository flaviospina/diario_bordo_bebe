<?php

use App\Core\Csrf;

/** @var array $categoria */
/** @var array $schema */
/** @var array $crianca */
/** @var int $blocoId */
/** @var string $dataPadrao */
/** @var string $politicaFotos */

$statusPadrao = in_array($_GET['status'] ?? '', ['feito', 'parcial', 'nao_feito'], true)
    ? (string)$_GET['status'] : 'feito';
?>
<h2><?= e($categoria['icone']) ?> <?= e($categoria['nome']) ?>
    <small class="texto-apoio">· <?= e($crianca['apelido'] ?: $crianca['nome']) ?></small></h2>

<form method="post" action="<?= e(url('registro.criar.salvar', ['categoria' => $categoria['slug']])) ?>"
      enctype="multipart/form-data" class="formulario formulario-registro" data-form-registro
      data-categoria="<?= e($categoria['slug']) ?>">
    <?= Csrf::campo() ?>
    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
    <input type="hidden" name="bloco" value="<?= (int)$blocoId ?>">
    <input type="hidden" name="uuid_cliente" value="" data-uuid-cliente>

    <div class="linha-campos">
        <div>
            <label for="inicio_data">Data</label>
            <input type="date" id="inicio_data" name="inicio_data" value="<?= e($dataPadrao) ?>" required>
        </div>
        <div>
            <label for="inicio_hora">Hora</label>
            <input type="time" id="inicio_hora" name="inicio_hora" value="<?= e(date('H:i')) ?>" required>
        </div>
        <div>
            <label for="fim_hora">Fim (opcional)</label>
            <input type="time" id="fim_hora" name="fim_hora">
            <input type="hidden" name="fim_data" value="<?= e($dataPadrao) ?>">
        </div>
    </div>

    <label>Status</label>
    <div class="grupo-opcoes">
        <?php foreach (['feito' => '✔ Feito', 'parcial' => '◐ Parcial', 'nao_feito' => '✖ Não feito'] as $valor => $rotulo): ?>
            <label class="opcao-botao">
                <input type="radio" name="status" value="<?= e($valor) ?>" <?= $statusPadrao === $valor ? 'checked' : '' ?>>
                <span><?= e($rotulo) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div data-so-nao-feito hidden>
        <label for="justificativa">Por que não foi feito? *</label>
        <textarea id="justificativa" name="justificativa" rows="2"></textarea>
    </div>

    <?php $dados = []; require RAIZ_PROJETO . '/app/Views/registro/_campos.php'; ?>

    <label for="observacao">Observação <button type="button" class="botao-voz" data-voz-para="observacao" hidden>🎤 Falar</button></label>
    <textarea id="observacao" name="observacao" rows="2" maxlength="2000"></textarea>

    <?php if ($politicaFotos !== 'desativada'): ?>
        <label for="foto">Foto<?= $politicaFotos === 'obrigatoria' ? ' *' : ' (opcional)' ?></label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment">
    <?php endif; ?>

    <button type="submit" class="botao botao-primario botao-largo">Salvar registro</button>
</form>
<p class="texto-apoio"><a href="<?= e(url('cuidador.dia.data', ['data' => $dataPadrao])) ?>">← Voltar para o dia</a></p>

<script src="<?= e(asset('js/formularios.js')) ?>" defer></script>
<script src="<?= e(asset('js/voz.js')) ?>" defer></script>
