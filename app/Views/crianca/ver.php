<?php
/** @var array $crianca */

$campos = [
    'Apelido' => $crianca['apelido'],
    'Nascimento' => data_br($crianca['data_nascimento'], 'd/m/Y'),
    'Tipo sanguíneo' => $crianca['tipo_sanguineo'],
    'Alergias' => $crianca['alergias'],
    'Condições de saúde' => $crianca['condicoes_saude'],
    'Medicações contínuas' => $crianca['medicacoes_continuas'],
    'Pediatra' => trim((string)$crianca['pediatra_nome'] . ' ' . (string)$crianca['pediatra_telefone']),
];
?>
<h2>🧒 <?= e($crianca['nome']) ?></h2>
<div class="cartao">
    <?php foreach ($campos as $rotulo => $valor): ?>
        <?php if ($valor !== null && trim((string)$valor) !== ''): ?>
            <p><strong><?= e($rotulo) ?>:</strong> <?= e((string)$valor) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($crianca['data_nascimento'] !== null): ?>
        <?php
        $meses = (new DateTime((string)$crianca['data_nascimento']))->diff(new DateTime('now'));
        $idade = $meses->y > 0 ? $meses->y . ' ano(s) e ' . $meses->m . ' mes(es)' : $meses->m . ' mes(es) e ' . $meses->d . ' dia(s)';
        ?>
        <p><strong>Idade:</strong> <?= e($idade) ?></p>
    <?php endif; ?>
</div>
<a class="botao botao-primario" href="<?= e(url('crianca.timeline', ['slug' => $crianca['slug']])) ?>">Linha do tempo</a>
