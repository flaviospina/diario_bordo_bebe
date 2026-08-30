<?php

use App\Core\Csrf;

/** @var array $turnos */
/** @var bool $podeAjustar */
?>
<h2>Turnos</h2>
<p class="texto-apoio">A entrada abre automaticamente no primeiro registro do dia do cuidador.
    Ajustes manuais são permitidos e ficam auditados.</p>

<?php if ($turnos === []): ?>
    <div class="cartao"><p class="texto-apoio">Nenhum turno registrado ainda.</p></div>
<?php endif; ?>

<?php foreach ($turnos as $turno): ?>
    <div class="cartao">
        <p><strong><?= e($turno['usuario_nome']) ?></strong> ·
            <?= e(data_br($turno['entrada'], 'd/m/Y')) ?><br>
            Entrada: <?= e(data_br($turno['entrada'], 'H:i')) ?>
            <?= (int)$turno['entrada_manual'] === 1 ? ' <em>(ajustada manualmente)</em>' : ' (automática)' ?> ·
            Saída: <?= $turno['saida'] !== null ? e(data_br($turno['saida'], 'H:i')) : 'em aberto' ?>
            <?= $turno['observacao'] !== null ? '<br>Obs.: ' . e($turno['observacao']) : '' ?></p>
        <?php if ($podeAjustar): ?>
            <details>
                <summary class="texto-apoio">Ajustar horários</summary>
                <form method="post" action="<?= e(url('turnos.acao')) ?>" class="formulario">
                    <?= Csrf::campo() ?>
                    <input type="hidden" name="turno_id" value="<?= (int)$turno['id'] ?>">
                    <div class="linha-campos">
                        <div>
                            <label>Entrada</label>
                            <input type="datetime-local" name="entrada"
                                   value="<?= e(str_replace(' ', 'T', substr((string)$turno['entrada'], 0, 16))) ?>" required>
                        </div>
                        <div>
                            <label>Saída</label>
                            <input type="datetime-local" name="saida"
                                   value="<?= $turno['saida'] !== null ? e(str_replace(' ', 'T', substr((string)$turno['saida'], 0, 16))) : '' ?>">
                        </div>
                    </div>
                    <label>Observação do ajuste</label>
                    <input type="text" name="observacao" maxlength="255" value="<?= e((string)$turno['observacao']) ?>">
                    <button type="submit" class="botao botao-pequeno botao-primario">Salvar ajuste</button>
                </form>
            </details>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
