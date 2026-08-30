<?php

use App\Core\Csrf;

/** @var array $suprimentos */
/** @var bool $podeResolver */

$niveis = ['ok' => '🟢 Ok', 'baixo' => '🟡 Acabando', 'acabou' => '🔴 Acabou'];
?>
<h2>Suprimentos</h2>

<div class="cartao">
    <h3>Pedir item</h3>
    <form method="post" action="<?= e(url('suprimentos.acao')) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <input type="hidden" name="acao" value="pedir">
        <div class="linha-campos">
            <div>
                <label for="item">Item</label>
                <input type="text" id="item" name="item" required maxlength="120" placeholder="Fraldas M, lenços...">
            </div>
            <div>
                <label for="nivel">Situação</label>
                <select id="nivel" name="nivel">
                    <option value="baixo">Está acabando</option>
                    <option value="acabou">Acabou</option>
                </select>
            </div>
        </div>
        <button type="submit" class="botao botao-primario">Registrar pedido</button>
    </form>
</div>

<?php if ($suprimentos !== []): ?>
<div class="cartao">
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Item</th><th>Situação</th><th>Pedido por</th><th>Quando</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($suprimentos as $item): ?>
            <tr class="<?= $item['resolvido_em'] !== null ? 'linha-inativa' : '' ?>">
                <td><?= e($item['item']) ?></td>
                <td><?= $item['resolvido_em'] !== null ? '✅ Resolvido' : e($niveis[$item['nivel']] ?? $item['nivel']) ?></td>
                <td><?= e($item['solicitante_nome']) ?></td>
                <td><?= e(data_br($item['solicitado_em'], 'd/m H:i')) ?></td>
                <td>
                    <?php if ($item['resolvido_em'] === null && $podeResolver): ?>
                        <form method="post" action="<?= e(url('suprimentos.acao')) ?>" class="form-inline">
                            <?= Csrf::campo() ?>
                            <input type="hidden" name="acao" value="resolver">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <button type="submit" class="botao botao-pequeno botao-contorno">Resolver</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
