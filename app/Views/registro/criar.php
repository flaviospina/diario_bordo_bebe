<?php

use App\Core\Csrf;

/** @var array $categoria */
/** @var array $schema */
/** @var array $crianca */
/** @var int $blocoId */
/** @var string $dataPadrao */
/** @var string $politicaFotos */
/** @var array $prefill */
/** @var ?array $cardapioDia */
/** @var array $fotoObrigatoriaRefeicoes */

$prefill = $prefill ?? [];
$ehIntroducao = $categoria['slug'] === 'introducao-alimentar';
// Rodada 6: almoço/jantar da introdução alimentar sempre têm campo de foto (obrigatório)
$mostraFoto = $politicaFotos !== 'desativada' || $ehIntroducao;

$statusPadrao = in_array($_GET['status'] ?? '', ['feito', 'parcial', 'nao_feito'], true)
    ? (string)$_GET['status'] : 'feito';
$horaPadrao = is_string($_GET['hora'] ?? null) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $_GET['hora']) === 1
    ? (string)$_GET['hora'] : date('H:i');
?>
<div style="display:flex; align-items:center; gap:.7rem; margin-bottom:.9rem">
    <?= selo_categoria((string)$categoria['slug'], (string)$categoria['grupo'], 48, 24) ?>
    <div>
        <h2 style="margin:0"><?= e($categoria['nome']) ?></h2>
        <span class="texto-apoio"><?= e($crianca['apelido'] ?: $crianca['nome']) ?></span>
    </div>
</div>

<form method="post" action="<?= e(url('registro.criar.salvar', ['categoria' => $categoria['slug']])) ?>"
      enctype="multipart/form-data" class="formulario formulario-registro" data-form-registro
      data-categoria="<?= e($categoria['slug']) ?>"
      <?= $ehIntroducao ? 'data-foto-obrigatoria-refeicoes="' . e(implode(',', $fotoObrigatoriaRefeicoes ?? ['almoco', 'jantar'])) . '"' : '' ?>>
    <?= Csrf::campo() ?>
    <input type="hidden" name="crianca" value="<?= e($crianca['slug']) ?>">
    <input type="hidden" name="bloco" value="<?= (int)$blocoId ?>">
    <input type="hidden" name="uuid_cliente" value="" data-uuid-cliente>

    <div class="linha-campos">
        <div>
            <label for="inicio_data">Data</label>
            <input type="date" id="inicio_data" name="inicio_data" value="<?= e($dataPadrao) ?>" required>
        </div>
        <div>
            <label for="inicio_hora">Hora</label>
            <input type="time" id="inicio_hora" name="inicio_hora" value="<?= e($horaPadrao) ?>" required>
        </div>
        <div>
            <label for="fim_hora">Fim (opcional)</label>
            <input type="time" id="fim_hora" name="fim_hora">
            <input type="hidden" name="fim_data" value="<?= e($dataPadrao) ?>">
        </div>
    </div>

    <label>Status</label>
    <div class="grupo-opcoes">
        <?php foreach (['feito' => '✔ Feito', 'parcial' => '◐ Parcial', 'nao_feito' => '✖ Não feito'] as $valor => $rotulo): ?>
            <label class="opcao-botao">
                <input type="radio" name="status" value="<?= e($valor) ?>" <?= $statusPadrao === $valor ? 'checked' : '' ?>>
                <span><?= e($rotulo) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div data-so-nao-feito hidden>
        <label for="justificativa">Por que não foi feito? *</label>
        <textarea id="justificativa" name="justificativa" rows="2"></textarea>
    </div>

    <?php if ($ehIntroducao && !empty($cardapioDia)): ?>
        <?php
        $linhasCardapio = array_filter([
            'Fruta da manhã' => trim((string)($cardapioDia['fruta_manha'] ?? '')),
            'Almoço' => trim((string)($cardapioDia['almoco'] ?? '')),
            'Fruta da tarde' => trim((string)($cardapioDia['fruta_tarde'] ?? '')),
            'Jantar' => trim((string)($cardapioDia['jantar'] ?? '')),
        ]);
        ?>
        <?php if ($linhasCardapio !== []): ?>
            <div class="cardapio-no-registro" data-cardapio-dia>
                <strong>Cardápio do dia</strong> <span class="texto-apoio">(<?= e((string)$cardapioDia['semana_titulo']) ?>)</span>
                <ul>
                    <?php foreach ($linhasCardapio as $rotuloRefeicao => $textoRefeicao): ?>
                        <li><strong><?= e($rotuloRefeicao) ?>:</strong> <?= e($textoRefeicao) ?></li>
                    <?php endforeach; ?>
                </ul>
                <span class="texto-apoio">Pode ser diferente do planejado — marque abaixo o que o bebê realmente comeu.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php $dados = $prefill; require RAIZ_PROJETO . '/app/Views/registro/_campos.php'; ?>

    <label for="observacao">Observação <button type="button" class="botao-voz" data-voz-para="observacao" hidden>🎤 Falar</button></label>
    <textarea id="observacao" name="observacao" rows="2" maxlength="2000"></textarea>

    <?php if ($mostraFoto): ?>
        <label for="foto" data-rotulo-foto data-rotulo-base="Foto<?= $politicaFotos === 'obrigatoria' ? ' *' : ' (opcional)' ?>">Foto<?= $politicaFotos === 'obrigatoria' ? ' *' : ' (opcional)' ?></label>
        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" capture="environment"
               <?= $politicaFotos === 'obrigatoria' ? 'required' : '' ?>>
        <?php if ($ehIntroducao): ?>
            <p class="texto-apoio foto-obrigatoria-aviso" data-aviso-foto-refeicao hidden style="margin:-.3rem 0 .8rem">
                📷 No almoço e no jantar a foto do prato é obrigatória para salvar o registro.</p>
        <?php endif; ?>
    <?php endif; ?>

    <button type="submit" class="botao botao-primario botao-largo">Salvar registro</button>
</form>
<p class="texto-apoio"><a href="<?= e(url('cuidador.dia.data', ['data' => $dataPadrao])) ?>">← Voltar para o dia</a></p>

<script src="<?= e(asset('js/formularios.js')) ?>" defer></script>
<script src="<?= e(asset('js/voz.js')) ?>" defer></script>
