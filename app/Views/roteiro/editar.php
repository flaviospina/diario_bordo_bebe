<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $criancas */
/** @var array $blocos */
/** @var array $categorias */
/** @var bool $roteiroAtivo */

$nomesDias = ['dom' => 'D', 'seg' => 'S', 'ter' => 'T', 'qua' => 'Q', 'qui' => 'Q', 'sex' => 'S', 'sab' => 'S'];
$rotulosDias = ['dom' => 'Dom', 'seg' => 'Seg', 'ter' => 'Ter', 'qua' => 'Qua', 'qui' => 'Qui', 'sex' => 'Sex', 'sab' => 'Sáb'];
$editandoId = (int)($_GET['bloco'] ?? 0);
$editando = null;
foreach ($blocos as $b) {
    if ((int)$b['id'] === $editandoId) {
        $editando = $b;
        break;
    }
}
$diasDoEditando = $editando !== null ? explode(',', (string)$editando['dias_semana']) : ['seg', 'ter', 'qua', 'qui', 'sex'];
?>
<h2>Roteiro do dia — <?= e($crianca['apelido'] ?: $crianca['nome']) ?></h2>

<?php if (!$roteiroAtivo): ?>
    <div class="alerta alerta-aviso">O roteiro prescrito está <strong>desativado</strong> nas
        <a href="<?= e(url('config.familia')) ?>">configurações da família</a>. Você pode montá-lo agora;
        ele só aparece para o cuidador quando for ativado.</div>
<?php endif; ?>

<?php if (count($criancas) > 1): ?>
    <form method="get" action="<?= e(url('roteiro.editar')) ?>" class="form-inline" style="margin-bottom:1rem">
        <select name="crianca" onchange="this.form.submit()" aria-label="Criança">
            <?php foreach ($criancas as $opcao): ?>
                <option value="<?= e($opcao['slug']) ?>" <?= $opcao['slug'] === $crianca['slug'] ? 'selected' : '' ?>>
                    <?= e($opcao['apelido'] ?: $opcao['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<?php foreach ($blocos as $bloco): ?>
    <div class="cartao linha-roteiro">
        <div>
            <strong><?= e(substr((string)$bloco['hora_inicio'], 0, 5)) ?>–<?= e(substr((string)$bloco['hora_fim'], 0, 5)) ?>
                · <?= e($bloco['categoria_icone'] ?? '📌') ?> <?= e($bloco['titulo']) ?></strong>
            <?= (int)$bloco['obrigatorio'] === 1 ? '<span class="etiqueta-estado">obrigatório</span>' : '' ?><br>
            <span class="texto-apoio">
                <?php foreach (explode(',', (string)$bloco['dias_semana']) as $dia): ?>
                    <?= e($rotulosDias[$dia] ?? $dia) ?><?= ' ' ?>
                <?php endforeach; ?>
                <?= $bloco['instrucao'] !== null ? '· ' . e($bloco['instrucao']) : '' ?>
            </span>
        </div>
        <div class="acoes-bloco">
            <a class="botao botao-pequeno botao-contorno" href="<?= e(url('roteiro.editar')) ?>?bloco=<?= (int)$bloco['id'] ?>">Editar</a>
            <form method="post" action="<?= e(url('roteiro.salvar')) ?>" class="form-inline">
                <?= Csrf::campo() ?>
                <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
                <input type="hidden" name="acao" value="remover">
                <input type="hidden" name="bloco_id" value="<?= (int)$bloco['id'] ?>">
                <button type="submit" class="botao botao-pequeno botao-contorno">Remover</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="cartao">
    <h3><?= $editando !== null ? 'Editar bloco' : 'Adicionar bloco' ?></h3>
    <form method="post" action="<?= e(url('roteiro.salvar')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
        <input type="hidden" name="acao" value="<?= $editando !== null ? 'atualizar' : 'criar' ?>">
        <input type="hidden" name="bloco_id" value="<?= (int)($editando['id'] ?? 0) ?>">

        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" required maxlength="120"
               placeholder="Mamadeira da manhã" value="<?= e((string)($editando['titulo'] ?? '')) ?>">

        <div class="linha-campos">
            <div>
                <label for="hora_inicio">Início *</label>
                <input type="time" id="hora_inicio" name="hora_inicio" required
                       value="<?= e(substr((string)($editando['hora_inicio'] ?? '08:00'), 0, 5)) ?>">
            </div>
            <div>
                <label for="hora_fim">Fim *</label>
                <input type="time" id="hora_fim" name="hora_fim" required
                       value="<?= e(substr((string)($editando['hora_fim'] ?? '08:30'), 0, 5)) ?>">
            </div>
            <div>
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">— livre —</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= e($categoria['slug']) ?>"
                            <?= ($editando['categoria_slug'] ?? '') === $categoria['slug'] ? 'selected' : '' ?>>
                            <?= e($categoria['icone']) ?> <?= e($categoria['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label>Dias da semana * (marque vários para aplicar o mesmo bloco)</label>
        <div class="grupo-opcoes">
            <?php foreach ($rotulosDias as $valor => $rotulo): ?>
                <label class="opcao-botao">
                    <input type="checkbox" name="dias[]" value="<?= e($valor) ?>"
                        <?= in_array($valor, $diasDoEditando, true) ? 'checked' : '' ?>>
                    <span><?= e($rotulo) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <label for="instrucao">Instrução para o cuidador</label>
        <textarea id="instrucao" name="instrucao" rows="2"
                  placeholder="Ex.: 120 ml de fórmula, morna. Arrotar depois."><?= e((string)($editando['instrucao'] ?? '')) ?></textarea>

        <label class="caixa-selecao">
            <input type="checkbox" name="obrigatorio" value="1" <?= (int)($editando['obrigatorio'] ?? 0) === 1 ? 'checked' : '' ?>>
            <span>Bloco obrigatório (fica âmbar/vermelho se passar sem registro)</span>
        </label>

        <button type="submit" class="botao botao-primario botao-largo">
            <?= $editando !== null ? 'Salvar bloco' : 'Adicionar bloco' ?>
        </button>
    </form>
</div>
