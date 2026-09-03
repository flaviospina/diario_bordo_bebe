<?php

use App\Core\Csrf;

/** @var array $categorias */
/** @var array $crianca */
/** @var string $dataPadrao */

$horaPadrao = is_string($_GET['hora'] ?? null) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_GET['hora']) === 1
    ? (string)$_GET['hora'] : date('H:i');
?>
<h2>Registrar <?= count($categorias) ?> atividades juntas</h2>
<p class="texto-apoio"><?= e($crianca['apelido'] ?: $crianca['nome']) ?> — tudo no mesmo horário,
    agrupado na linha do tempo. Para foto ou horário de fim, registre a atividade sozinha.</p>

<form method="post" action="<?= e(url('registro.varios.salvar')) ?>" class="formulario">
    <?= Csrf::campo() ?>
    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
    <input type="hidden" name="categorias" value="<?= e(implode(',', array_column($categorias, 'slug'))) ?>">

    <div class="linha-campos">
        <div>
            <label for="inicio_data">Data</label>
            <input type="date" id="inicio_data" name="inicio_data" value="<?= e($dataPadrao) ?>" required>
        </div>
        <div>
            <label for="inicio_hora">Hora</label>
            <input type="time" id="inicio_hora" name="inicio_hora" value="<?= e($horaPadrao) ?>" required>
        </div>
    </div>

    <?php foreach ($categorias as $categoria): ?>
        <fieldset class="bloco-multiatividade">
            <legend>
                <?= selo_categoria((string)$categoria['slug'], (string)$categoria['grupo'], 36, 18) ?>
                <span><?= e($categoria['nome']) ?></span>
            </legend>
            <?php
            $schema = json_decode((string)$categoria['schema_campos'], true) ?: ['campos' => []];
            $dados = [];
            $prefixoCampos = 'm_' . $categoria['slug'] . '_c_';
            require RAIZ_PROJETO . '/app/Views/registro/_campos.php';
            unset($prefixoCampos);
            ?>
            <label for="obs_<?= e($categoria['slug']) ?>">Observação <small>(opcional)</small></label>
            <input type="text" id="obs_<?= e($categoria['slug']) ?>" name="obs_<?= e($categoria['slug']) ?>" maxlength="500">
        </fieldset>
    <?php endforeach; ?>

    <button type="submit" class="botao botao-primario botao-largo">
        Salvar as <?= count($categorias) ?> atividades
    </button>
</form>
<div class="acoes-pagina">
    <a class="botao botao-contorno" href="<?= e(url('cuidador.dia.data', ['data' => $dataPadrao])) ?>">
        <?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?> Voltar para o dia</a>
</div>
