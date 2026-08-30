<?php
/** @var array $crianca */
/** @var array $registros */
/** @var array $categorias */
/** @var array $filtros */
?>
<h2>Linha do tempo — <?= e($crianca['apelido'] ?: $crianca['nome']) ?></h2>

<form method="get" action="<?= e(url('crianca.timeline', ['slug' => $crianca['slug']])) ?>" class="cartao formulario">
    <div class="linha-campos">
        <div>
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= e($categoria['slug']) ?>" <?= $filtros['categoria'] === $categoria['slug'] ? 'selected' : '' ?>>
                        <?= e($categoria['icone']) ?> <?= e($categoria['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="de">De</label>
            <input type="date" id="de" name="de" value="<?= e($filtros['de']) ?>">
        </div>
        <div>
            <label for="ate">Até</label>
            <input type="date" id="ate" name="ate" value="<?= e($filtros['ate']) ?>">
        </div>
    </div>
    <button type="submit" class="botao botao-primario">Filtrar</button>
</form>

<?php if ($registros === []): ?>
    <p class="texto-apoio">Nada encontrado com esses filtros.</p>
<?php endif; ?>

<?php $diaAtual = ''; ?>
<?php foreach ($registros as $registro): ?>
    <?php $dia = substr((string)$registro['inicio'], 0, 10); ?>
    <?php if ($dia !== $diaAtual): $diaAtual = $dia; ?>
        <h3><?= e(data_br($dia . ' 00:00:00', 'd/m/Y')) ?></h3>
    <?php endif; ?>
    <a class="chip-registro estado-<?= e($registro['status'] === 'feito' ? 'verde' : ($registro['status'] === 'parcial' ? 'ambar' : 'vermelho')) ?>"
       href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">
        <span><?= e($registro['categoria_icone']) ?></span>
        <span><?= e(data_br($registro['inicio'], 'H:i')) ?> · <?= e($registro['categoria_nome']) ?>
            <small class="texto-apoio">(<?= e($registro['usuario_nome']) ?>)</small></span>
    </a><br>
<?php endforeach; ?>
