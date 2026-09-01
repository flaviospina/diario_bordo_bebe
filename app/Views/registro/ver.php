<?php

use App\Core\Csrf;

/** @var array $registro */
/** @var array $dados */
/** @var array $schema */
/** @var array $fotos */
/** @var string $permissao */
/** @var array $versoes */

$rotulosCampos = [];
foreach (($schema['campos'] ?? []) as $campo) {
    $rotulos = [];
    foreach (($campo['opcoes'] ?? []) as $opcao) {
        $rotulos[$opcao['valor']] = $opcao['rotulo'];
    }
    $rotulosCampos[$campo['nome']] = ['rotulo' => $campo['rotulo'], 'opcoes' => $rotulos];
}
$statusRotulo = ['feito' => '✔ Feito', 'parcial' => '◐ Parcial', 'nao_feito' => '✖ Não feito'];
?>
<div style="display:flex; align-items:center; gap:.7rem; margin-bottom:.9rem">
    <?= selo_categoria((string)$registro['categoria_slug'], (string)$registro['categoria_grupo'], 48, 24) ?>
    <h2 style="margin:0"><?= e($registro['categoria_nome']) ?></h2>
</div>

<?php if ($registro['excluido_em'] !== null): ?>
    <div class="alerta alerta-erro">Registro excluído em <?= e(data_br($registro['excluido_em'])) ?>.
        Motivo: <?= e((string)$registro['motivo_exclusao']) ?></div>
<?php endif; ?>

<div class="cartao">
    <p><strong>Criança:</strong> <?= e($registro['crianca_nome']) ?><br>
       <strong>Quando:</strong> <?= e(data_br($registro['inicio'])) ?>
       <?= $registro['fim'] !== null ? ' até ' . e(data_br($registro['fim'], 'H:i')) : '' ?><br>
       <strong>Status:</strong> <?= e($statusRotulo[$registro['status']] ?? $registro['status']) ?><br>
       <strong>Registrado por:</strong> <?= e($registro['usuario_nome']) ?> em <?= e(data_br($registro['criado_em'])) ?></p>

    <?php if ($registro['justificativa'] !== null): ?>
        <p><strong>Justificativa:</strong> <?= e($registro['justificativa']) ?></p>
    <?php endif; ?>

    <?php if ($dados !== []): ?>
        <hr>
        <?php foreach ($dados as $nome => $valor): ?>
            <p><strong><?= e($rotulosCampos[$nome]['rotulo'] ?? $nome) ?>:</strong>
                <?= e((string)($rotulosCampos[$nome]['opcoes'][$valor] ?? $valor)) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($registro['observacao'] !== null): ?>
        <p><strong>Observação:</strong> <?= e($registro['observacao']) ?></p>
    <?php endif; ?>

    <?php if ($fotos !== []): ?>
        <div class="fotos-registro">
            <?php foreach ($fotos as $foto): ?>
                <a href="<?= e(url('foto.ver', ['codigo' => $foto['codigo_publico']])) ?>">
                    <img src="<?= e(url('foto.ver', ['codigo' => $foto['codigo_publico']])) ?>?thumb=1"
                         alt="Foto do registro" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($permissao === 'editar'): ?>
    <a class="botao botao-primario" href="<?= e(url('registro.editar', ['codigo' => $registro['codigo_publico']])) ?>">Editar</a>
<?php elseif ($permissao === 'solicitar'): ?>
    <a class="botao botao-primario" href="<?= e(url('registro.solicitar', ['codigo' => $registro['codigo_publico']])) ?>">Solicitar alteração</a>
<?php endif; ?>

<?php if ($permissao !== 'nada' && $registro['excluido_em'] === null): ?>
    <details class="cartao" style="margin-top:1rem">
        <summary>Excluir registro</summary>
        <form method="post" action="<?= e(url('registro.excluir', ['codigo' => $registro['codigo_publico']])) ?>" class="formulario">
            <?= Csrf::campo() ?>
            <label for="motivo_exclusao">Motivo (obrigatório — a exclusão é lógica e fica auditada)</label>
            <textarea id="motivo_exclusao" name="motivo" rows="2" required></textarea>
            <button type="submit" class="botao botao-primario">
                <?= $permissao === 'editar' ? 'Excluir' : 'Solicitar exclusão aos responsáveis' ?>
            </button>
        </form>
    </details>
<?php endif; ?>

<?php if ($versoes !== []): ?>
    <details class="cartao" style="margin-top:1rem">
        <summary>Histórico de versões (<?= count($versoes) ?>)</summary>
        <?php foreach ($versoes as $versao): ?>
            <div class="versao-item">
                <p><strong><?= e(data_br($versao['criado_em'])) ?></strong> por <?= e($versao['usuario_nome']) ?>
                    <?= $versao['motivo'] !== null ? '— ' . e($versao['motivo']) : '' ?></p>
                <pre class="pre-versao"><?= e(json_encode(json_decode((string)$versao['dados_anteriores'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
            </div>
        <?php endforeach; ?>
    </details>
<?php endif; ?>

<p class="texto-apoio" style="margin-top:1rem">
    <a href="<?= e(url('cuidador.dia.data', ['data' => substr((string)$registro['inicio'], 0, 10)])) ?>">← Voltar para o dia</a>
</p>
