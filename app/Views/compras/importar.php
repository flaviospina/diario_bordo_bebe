<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $semana */
/** @var array $marcar itens que serão marcados */
/** @var int $desconhecidos */
/** @var ?string $atualizadoEm */
?>
<h2>Importar marcações — <?= e($semana['titulo']) ?></h2>
<div class="cartao">
    <p>O arquivo foi lido com segurança (só o estado salvo, sem executar nada).
        <?php if ($atualizadoEm !== null): ?>Marcações salvas em <strong><?= e(data_br($atualizadoEm)) ?></strong>.<?php endif; ?></p>
    <p><strong><?= count($marcar) ?></strong> item(ns) serão marcados como "no carrinho"<?= $desconhecidos > 0 ? ' · ' . (int)$desconhecidos . ' identificador(es) desconhecido(s) serão ignorados' : '' ?>.
        Itens já marcados no app continuam marcados; nenhum item será desmarcado.</p>
    <?php if ($marcar === []): ?>
        <p class="texto-apoio">Nenhum item reconhecido para marcar.</p>
    <?php else: ?>
        <p class="lista-chips"><?php foreach ($marcar as $item): ?><span class="chip-alimento"><?= e($item['nome']) ?></span> <?php endforeach; ?></p>
        <form method="post" action="<?= e(url('compras.acao')) ?>" class="form-inline" style="margin-top:.8rem">
            <?= Csrf::campo() ?>
            <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
            <input type="hidden" name="acao" value="importar_aplicar">
            <input type="hidden" name="semana_id" value="<?= (int)$semana['id'] ?>">
            <?php foreach ($marcar as $item): ?><input type="hidden" name="ids[]" value="<?= e($item['stable_id']) ?>"><?php endforeach; ?>
            <button type="submit" class="botao botao-primario">Aplicar <?= count($marcar) ?> marcação(ões)</button>
            <a class="botao botao-contorno" href="<?= e(url('compras')) ?>?crianca=<?= e($crianca['slug']) ?>&semana=<?= (int)$semana['id'] ?>">Cancelar</a>
        </form>
    <?php endif; ?>
</div>
