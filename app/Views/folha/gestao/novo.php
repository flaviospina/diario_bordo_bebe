<?php
use App\Core\Csrf;
use App\Services\ServicoFolha;
/** @var array $colaboradoras */ /** @var ?array $colaboradora */ /** @var string $tipo */ /** @var string $competencia */ /** @var int $limiteMb */
?>
<p class="texto-apoio"><a href="<?= e(url('portal.gestao')) ?>">← Gestão</a></p>
<div class="portal-cabeca"><h2>Importar documento</h2>
    <p class="texto-apoio">Envie o PDF obtido no eSocial. Ele fica em área privada, como rascunho, até você conferir e publicar.</p></div>
<form method="post" action="<?= e(url('portal.gestao.novo.salvar')) ?>" enctype="multipart/form-data" class="formulario cartao" data-form-upload>
    <?= Csrf::campo() ?>
    <div class="linha-campos">
        <div><label for="n_colab">Colaboradora</label><select id="n_colab" name="colaboradora_id" required>
            <?php foreach ($colaboradoras as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $colaboradora !== null && $c['id'] === $colaboradora['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
        <div><label for="n_tipo">Tipo de documento</label><select id="n_tipo" name="tipo" data-tipo-doc>
            <?php foreach (ServicoFolha::TIPOS as $k => $v): ?><option value="<?= e($k) ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="linha-campos">
        <div><label for="n_comp">Competência *</label><input type="month" id="n_comp" name="competencia" required value="<?= e($competencia) ?>"></div>
        <div><label for="n_pag">Data do pagamento</label><input type="date" id="n_pag" name="data_pagamento"></div>
        <div data-so-vt <?= $tipo === 'vale_transporte' ? '' : 'hidden' ?>><label for="n_mes">Mês de referência (VT)</label><input type="month" id="n_mes" name="mes_referencia"></div>
    </div>
    <label for="n_desc">Descrição (opcional)</label>
    <input type="text" id="n_desc" name="descricao" maxlength="200" placeholder="Ex.: Recibo de pagamento mensal">
    <label for="n_arq">Arquivo PDF * <span class="texto-apoio">(até <?= (int)$limiteMb ?> MB)</span></label>
    <input type="file" id="n_arq" name="arquivo" accept="application/pdf,.pdf" required>
    <p class="texto-apoio">O arquivo é validado (PDF real, íntegro, sem scripts) e guardado com nome aleatório fora da área pública. Nome original, tamanho e hash SHA-256 ficam registrados.</p>
    <button type="submit" class="botao botao-primario botao-largo" data-enviando="Enviando…">Enviar e conferir</button>
</form>
<script nonce="<?= e(\App\Core\Resposta::nonce()) ?>">
(function () {
    var sel = document.querySelector('[data-tipo-doc]'), bloco = document.querySelector('[data-so-vt]');
    if (sel && bloco) { sel.addEventListener('change', function () { bloco.hidden = sel.value !== 'vale_transporte'; }); }
    var form = document.querySelector('[data-form-upload]');
    if (form) { form.addEventListener('submit', function () { var b = form.querySelector('[data-enviando]'); if (b) { b.disabled = true; b.textContent = b.dataset.enviando; } }); }
})();
</script>
