<?php
/**
 * Partial: renderiza os campos dinâmicos de uma categoria a partir do
 * schema_campos JSON — nenhum formulário de categoria é hardcoded.
 * Espera: array $schema (com 'campos') e array $dados (valores atuais).
 */
/** @var array $schema */
/** @var array $dados */
?>
<?php foreach (($schema['campos'] ?? []) as $campo): ?>
    <?php
    $nome = 'c_' . $campo['nome'];
    $id = 'campo_' . $campo['nome'];
    $atual = $dados[$campo['nome']] ?? null;
    $obrigatorio = !empty($campo['obrigatorio']);
    ?>
    <label for="<?= e($id) ?>"><?= e($campo['rotulo']) ?><?= $obrigatorio ? ' *' : '' ?></label>
    <?php switch ($campo['tipo']):
        case 'opcoes': ?>
            <div class="grupo-opcoes" role="radiogroup" aria-label="<?= e($campo['rotulo']) ?>">
                <?php foreach (($campo['opcoes'] ?? []) as $opcao): ?>
                    <label class="opcao-botao">
                        <input type="radio" name="<?= e($nome) ?>" value="<?= e($opcao['valor']) ?>"
                            <?= $atual === $opcao['valor'] ? 'checked' : '' ?> <?= $obrigatorio ? 'required' : '' ?>>
                        <span><?= e($opcao['rotulo']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php break; ?>
        <?php case 'numero': ?>
            <input type="number" inputmode="decimal" id="<?= e($id) ?>" name="<?= e($nome) ?>"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>"
                   <?= isset($campo['minimo']) ? 'min="' . e((string)$campo['minimo']) . '"' : '' ?>
                   <?= isset($campo['maximo']) ? 'max="' . e((string)$campo['maximo']) . '"' : '' ?>
                   <?= isset($campo['passo']) ? 'step="' . e((string)$campo['passo']) . '"' : 'step="any"' ?>
                   <?= $obrigatorio ? 'required' : '' ?>>
            <?php if (!empty($campo['unidade'])): ?><small class="texto-apoio"><?= e($campo['unidade']) ?></small><?php endif; ?>
            <?php break; ?>
        <?php case 'duracao_minutos': ?>
            <input type="number" inputmode="numeric" id="<?= e($id) ?>" name="<?= e($nome) ?>" min="0" max="1440"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>" <?= $obrigatorio ? 'required' : '' ?>>
            <small class="texto-apoio">minutos</small>
            <?php break; ?>
        <?php case 'escala': ?>
            <div class="grupo-opcoes grupo-escala" role="radiogroup" aria-label="<?= e($campo['rotulo']) ?>">
                <?php $maximo = (int)($campo['maximo'] ?? 5);
                $carinhas = ['😢', '🙁', '😐', '🙂', '😄']; ?>
                <?php for ($nivel = 1; $nivel <= $maximo; $nivel++): ?>
                    <label class="opcao-botao opcao-escala">
                        <input type="radio" name="<?= e($nome) ?>" value="<?= $nivel ?>"
                            <?= (int)$atual === $nivel ? 'checked' : '' ?> <?= $obrigatorio ? 'required' : '' ?>>
                        <span><?= e($carinhas[$nivel - 1] ?? (string)$nivel) ?></span>
                    </label>
                <?php endfor; ?>
            </div>
            <?php break; ?>
        <?php case 'texto_longo': ?>
            <textarea id="<?= e($id) ?>" name="<?= e($nome) ?>" rows="3" maxlength="2000"
                      <?= $obrigatorio ? 'required' : '' ?>><?= $atual !== null ? e((string)$atual) : '' ?></textarea>
            <?php break; ?>
        <?php default: ?>
            <input type="text" id="<?= e($id) ?>" name="<?= e($nome) ?>" maxlength="255"
                   value="<?= $atual !== null ? e((string)$atual) : '' ?>" <?= $obrigatorio ? 'required' : '' ?>>
    <?php endswitch; ?>
<?php endforeach; ?>
