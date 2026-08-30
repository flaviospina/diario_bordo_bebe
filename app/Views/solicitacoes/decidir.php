<?php

use App\Core\Csrf;

/** @var array $solicitacao */
/** @var array $payload */
?>
<h2>Solicitação de <?= e($solicitacao['tipo'] === 'exclusao' ? 'exclusão' : ($solicitacao['tipo'] === 'conflito_sync' ? 'revisão (conflito)' : 'edição')) ?></h2>

<div class="cartao">
    <p><strong>Registro:</strong>
        <a href="<?= e(url('registro.ver', ['codigo' => $solicitacao['registro_codigo']])) ?>">ver registro atual</a><br>
        <strong>Solicitante:</strong> <?= e($solicitacao['solicitante_nome']) ?>
        em <?= e(data_br($solicitacao['criado_em'])) ?><br>
        <strong>Motivo:</strong> <?= e($solicitacao['motivo']) ?></p>

    <?php if ($solicitacao['tipo'] !== 'exclusao' && $payload !== []): ?>
        <p><strong>Mudanças propostas:</strong></p>
        <pre class="pre-versao"><?= e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
    <?php endif; ?>
</div>

<?php if ($solicitacao['status'] === 'pendente'): ?>
    <form method="post" action="<?= e(url('solicitacoes.decidir', ['codigo' => $solicitacao['codigo_publico']])) ?>" class="formulario">
        <?= Csrf::campo() ?>
        <label for="resposta">Resposta (opcional)</label>
        <textarea id="resposta" name="resposta" rows="2"></textarea>
        <div class="linha-campos">
            <button type="submit" name="decisao" value="aprovar" class="botao botao-primario">Aprovar e aplicar</button>
            <button type="submit" name="decisao" value="recusar" class="botao botao-contorno">Recusar</button>
        </div>
    </form>
<?php else: ?>
    <p>Decidida: <strong><?= $solicitacao['status'] === 'aprovada' ? 'aprovada' : 'recusada' ?></strong>
        em <?= e(data_br($solicitacao['decidido_em'])) ?>.
        <?= $solicitacao['resposta'] !== null ? 'Resposta: ' . e($solicitacao['resposta']) : '' ?></p>
<?php endif; ?>

<p class="texto-apoio"><a href="<?= e(url('solicitacoes.lista')) ?>">← Todas as solicitações</a></p>
