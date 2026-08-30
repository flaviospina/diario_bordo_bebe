<?php

use App\Core\Csrf;

/** @var array $registro */
/** @var array $dados */
/** @var array $schema */
/** @var string $modo 'editar' | 'solicitar' */

$acao = $modo === 'editar'
    ? url('registro.editar.salvar', ['codigo' => $registro['codigo_publico']])
    : url('registro.solicitar.enviar', ['codigo' => $registro['codigo_publico']]);
?>
<h2><?= $modo === 'editar' ? 'Editar' : 'Solicitar alteração' ?> — <?= e($registro['categoria_nome']) ?></h2>
<?php if ($modo === 'solicitar'): ?>
    <div class="alerta alerta-aviso">Este registro está fora da janela de edição configurada pela família.
        As mudanças propostas serão enviadas aos responsáveis para aprovação.</div>
<?php endif; ?>

<form method="post" action="<?= e($acao) ?>" class="formulario formulario-registro" data-form-registro
      <?= $modo === 'editar' ? 'data-codigo="' . e($registro['codigo_publico']) . '" data-atualizado-em="' . e((string)$registro['atualizado_em']) . '"' : '' ?>>
    <?= Csrf::campo() ?>

    <div class="linha-campos">
        <div>
            <label for="inicio_data">Data</label>
            <input type="date" id="inicio_data" name="inicio_data" required
                   value="<?= e(substr((string)$registro['inicio'], 0, 10)) ?>">
        </div>
        <div>
            <label for="inicio_hora">Hora</label>
            <input type="time" id="inicio_hora" name="inicio_hora" required
                   value="<?= e(substr((string)$registro['inicio'], 11, 5)) ?>">
        </div>
        <div>
            <label for="fim_hora">Fim (opcional)</label>
            <input type="time" id="fim_hora" name="fim_hora"
                   value="<?= $registro['fim'] !== null ? e(substr((string)$registro['fim'], 11, 5)) : '' ?>">
            <input type="hidden" name="fim_data" value="<?= e(substr((string)($registro['fim'] ?? $registro['inicio']), 0, 10)) ?>">
        </div>
    </div>

    <label>Status</label>
    <div class="grupo-opcoes">
        <?php foreach (['feito' => '✔ Feito', 'parcial' => '◐ Parcial', 'nao_feito' => '✖ Não feito'] as $valor => $rotulo): ?>
            <label class="opcao-botao">
                <input type="radio" name="status" value="<?= e($valor) ?>" <?= $registro['status'] === $valor ? 'checked' : '' ?>>
                <span><?= e($rotulo) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div data-so-nao-feito <?= $registro['status'] === 'nao_feito' ? '' : 'hidden' ?>>
        <label for="justificativa">Por que não foi feito? *</label>
        <textarea id="justificativa" name="justificativa" rows="2"><?= e((string)$registro['justificativa']) ?></textarea>
    </div>

    <?php require RAIZ_PROJETO . '/app/Views/registro/_campos.php'; ?>

    <label for="observacao">Observação</label>
    <textarea id="observacao" name="observacao" rows="2" maxlength="2000"><?= e((string)$registro['observacao']) ?></textarea>

    <label for="motivo"><?= $modo === 'editar' ? 'Motivo da edição (recomendado)' : 'Motivo da alteração *' ?></label>
    <textarea id="motivo" name="motivo" rows="2" <?= $modo === 'solicitar' ? 'required' : '' ?>></textarea>

    <button type="submit" class="botao botao-primario botao-largo">
        <?= $modo === 'editar' ? 'Salvar alterações' : 'Enviar solicitação' ?>
    </button>
</form>
<p class="texto-apoio">
    <a href="<?= e(url('registro.ver', ['codigo' => $registro['codigo_publico']])) ?>">← Voltar ao registro</a>
</p>
<script src="<?= e(asset('js/formularios.js')) ?>" defer></script>
