<?php
/** @var array $versoes */
/** @var array $acessos */
?>
<h2>Auditoria</h2>

<div class="cartao">
    <h3>Versões de registros (<?= count($versoes) ?>)</h3>
    <p class="texto-apoio">Nenhum registro é destruído: toda alteração guarda o estado anterior.</p>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Quando</th><th>Quem</th><th>Registro</th><th>Motivo</th></tr></thead>
        <tbody>
        <?php foreach ($versoes as $versao): ?>
            <tr>
                <td><?= e(data_br($versao['criado_em'])) ?></td>
                <td><?= e($versao['usuario_nome']) ?></td>
                <td><a href="<?= e(url('registro.ver', ['codigo' => $versao['registro_codigo']])) ?>">
                    <?= e($versao['categoria_nome']) ?> · <?= e($versao['crianca_nome']) ?></a></td>
                <td><?= e((string)$versao['motivo']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<div class="cartao">
    <h3>Acessos e ações (<?= count($acessos) ?>)</h3>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Quando</th><th>Quem</th><th>Ação</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($acessos as $acesso): ?>
            <tr>
                <td><?= e(data_br($acesso['criado_em'])) ?></td>
                <td><?= e($acesso['usuario_nome'] ?? '—') ?></td>
                <td><code><?= e($acesso['acao']) ?></code></td>
                <td><?= e((string)$acesso['ip']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
