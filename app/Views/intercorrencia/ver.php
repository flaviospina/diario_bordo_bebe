<?php

use App\Core\Csrf;

/** @var array $intercorrencia */
/** @var bool $podeDarCiencia */

$cores = ['leve' => 'aviso', 'moderada' => 'aviso', 'grave' => 'erro'];
?>
<h2>⚠️ Intercorrência — <?= e(ucfirst((string)$intercorrencia['gravidade'])) ?></h2>

<div class="alerta alerta-<?= e($cores[$intercorrencia['gravidade']] ?? 'aviso') ?>">
    <strong><?= e($intercorrencia['crianca_nome']) ?></strong> ·
    <?= e(data_br($intercorrencia['ocorrido_em'])) ?> ·
    registrado por <?= e($intercorrencia['usuario_nome']) ?>
</div>

<div class="cartao">
    <p><strong>O que aconteceu:</strong><br><?= nl2br(e((string)$intercorrencia['descricao'])) ?></p>
    <?php if ($intercorrencia['acao_tomada'] !== null): ?>
        <p><strong>O que foi feito:</strong><br><?= nl2br(e((string)$intercorrencia['acao_tomada'])) ?></p>
    <?php endif; ?>
</div>

<?php if ($intercorrencia['ciencia_em'] !== null): ?>
    <div class="alerta alerta-sucesso">
        ✅ Ciência registrada por <?= e((string)$intercorrencia['ciencia_nome']) ?>
        em <?= e(data_br($intercorrencia['ciencia_em'])) ?>.
    </div>
<?php elseif ($podeDarCiencia): ?>
    <form method="post" action="<?= e(url('intercorrencia.ciencia', ['codigo' => $intercorrencia['codigo_publico']])) ?>">
        <?= Csrf::campo() ?>
        <button type="submit" class="botao botao-primario botao-largo">Estou ciente desta intercorrência</button>
    </form>
<?php else: ?>
    <p class="texto-apoio">Aguardando ciência dos responsáveis.</p>
<?php endif; ?>

<p class="texto-apoio" style="margin-top:1rem"><a href="<?= e(url('pais.acompanhar')) ?>">← Voltar</a></p>
