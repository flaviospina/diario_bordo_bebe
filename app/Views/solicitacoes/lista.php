<?php
/** @var array $pendentes */
/** @var array $resolvidas */

$tipos = ['edicao' => 'Edição', 'exclusao' => 'Exclusão', 'conflito_sync' => 'Conflito de sincronização'];
?>
<h2>Solicitações</h2>

<h3>Pendentes (<?= count($pendentes) ?>)</h3>
<?php if ($pendentes === []): ?>
    <p class="texto-apoio">Nenhuma solicitação aguardando decisão.</p>
<?php endif; ?>
<?php foreach ($pendentes as $solicitacao): ?>
    <a class="cartao cartao-link" href="<?= e(url('solicitacoes.decidir', ['codigo' => $solicitacao['codigo_publico']])) ?>">
        <strong><?= e($tipos[$solicitacao['tipo']] ?? $solicitacao['tipo']) ?></strong> —
        <?= e($solicitacao['categoria_nome']) ?> de <?= e($solicitacao['crianca_nome']) ?><br>
        <span class="texto-apoio">Pedido por <?= e($solicitacao['solicitante_nome']) ?>
            em <?= e(data_br($solicitacao['criado_em'])) ?> · Motivo: <?= e(mb_substr((string)$solicitacao['motivo'], 0, 120)) ?></span>
    </a>
<?php endforeach; ?>

<?php if ($resolvidas !== []): ?>
    <h3>Decididas</h3>
    <div class="tabela-rolavel"><table class="tabela">
        <thead><tr><th>Tipo</th><th>Registro</th><th>Solicitante</th><th>Decisão</th><th>Quando</th></tr></thead>
        <tbody>
        <?php foreach ($resolvidas as $solicitacao): ?>
            <tr>
                <td><?= e($tipos[$solicitacao['tipo']] ?? $solicitacao['tipo']) ?></td>
                <td><a href="<?= e(url('registro.ver', ['codigo' => $solicitacao['registro_codigo']])) ?>"><?= e($solicitacao['categoria_nome']) ?></a></td>
                <td><?= e($solicitacao['solicitante_nome']) ?></td>
                <td><?= $solicitacao['status'] === 'aprovada' ? '✅ Aprovada' : '❌ Recusada' ?></td>
                <td><?= e(data_br($solicitacao['decidido_em'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
