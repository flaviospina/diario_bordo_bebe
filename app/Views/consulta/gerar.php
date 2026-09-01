<?php

use App\Core\Csrf;

/** @var array $crianca */
/** @var array $convites */

$nomeCurto = (string)($crianca['apelido'] ?: $crianca['nome']);
?>
<h2>Ficha para consulta</h2>
<p class="texto-apoio">Gere um QR code e mostre ao pediatra na consulta de <?= e($nomeCurto) ?>.
    O link abre a ficha essencial <strong>uma única vez</strong>, vale por <strong>48 horas</strong>
    e não mostra a rotina dia a dia nem fotos. O que o pediatra registrar volta para você confirmar.</p>

<form method="post" action="<?= e(url('consulta.gerar.enviar', ['slug' => $crianca['slug']])) ?>" class="formulario">
    <?= Csrf::campo() ?>
    <input type="hidden" name="acao" value="criar">
    <button type="submit" class="botao botao-primario botao-largo">
        <?= icone_ui('mais', 18, 'currentColor', 2.4) ?> Gerar novo link
    </button>
</form>

<?php if ($convites === []): ?>
    <div class="cartao">
        <p class="texto-apoio" style="margin:0">Nenhum link ativo no momento.
            Gere um pouco antes da consulta — ele expira sozinho em 48 h.</p>
    </div>
<?php endif; ?>

<?php foreach ($convites as $convite): ?>
    <?php $enderecoFicha = url_absoluta('consulta.ficha', ['codigo' => $convite['codigo_publico']]); ?>
    <div class="cartao cartao-link-consulta">
        <div class="link-consulta-info">
            <strong>Link <?= e(mb_substr((string)$convite['codigo_publico'], 0, 4)) ?>…</strong>
            <span class="texto-apoio">criado <?= e(data_br($convite['criado_em'], 'd/m H:i')) ?>
                · expira <?= e(data_br($convite['expira_em'], 'd/m H:i')) ?>
                <?= $convite['aberto_em'] !== null ? ' · já aberto' : '' ?></span>
        </div>
        <div class="link-consulta-acoes">
            <button type="button" class="botao botao-primario botao-pequeno" data-mostrar-qr
                    data-url="<?= e($enderecoFicha) ?>" data-nome="<?= e($nomeCurto) ?>">Mostrar QR</button>
            <button type="button" class="botao botao-contorno botao-pequeno" data-copiar-link
                    data-url="<?= e($enderecoFicha) ?>">Copiar link</button>
            <form method="post" action="<?= e(url('consulta.gerar.enviar', ['slug' => $crianca['slug']])) ?>" class="form-inline">
                <?= Csrf::campo() ?>
                <input type="hidden" name="acao" value="revogar">
                <input type="hidden" name="convite_id" value="<?= (int)$convite['id'] ?>">
                <button type="submit" class="botao botao-contorno botao-pequeno">Revogar</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="acoes-pagina">
    <a class="botao botao-contorno" href="<?= e(url('crianca.ver', ['slug' => $crianca['slug']])) ?>">
        <?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?> Ficha de <?= e($nomeCurto) ?></a>
</div>

<div class="qr-tela" data-qr-tela hidden>
    <div class="qr-caixa">
        <div data-qr-destino></div>
        <strong data-qr-nome></strong>
        <span class="texto-apoio">Peça para o pediatra apontar a câmera.<br>Toque em qualquer lugar para fechar.</span>
    </div>
</div>

<script src="<?= e(asset('js/qrcode.min.js')) ?>"></script>
<script src="<?= e(asset('js/consulta.js')) ?>" defer></script>
