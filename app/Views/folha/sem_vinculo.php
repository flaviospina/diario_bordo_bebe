<?php /** @var bool $instalado */ ?>
<div class="portal-cabeca">
    <h2>Meus Pagamentos</h2>
    <p class="texto-apoio">Consulte seus contracheques, benefícios e documentos trabalhistas.</p>
</div>
<div class="cartao portal-vazio">
    <span class="selo-categoria" style="width:48px;height:48px;background:var(--lavanda-suave)"><?= icone_ui('documento', 24, '#5A539E') ?></span>
    <?php if (!empty($instalado)): ?>
        <h3>Seu vínculo ainda não foi cadastrado</h3>
        <p class="texto-apoio">Assim que o empregador vincular o seu usuário ao Portal da Colaboradora, seus documentos aparecerão aqui.
            Nada do diário do bebê muda: continue registrando normalmente.</p>
    <?php else: ?>
        <h3>Portal ainda não ativado</h3>
        <p class="texto-apoio">O Portal da Colaboradora ainda não foi instalado nesta família.</p>
    <?php endif; ?>
    <a class="botao botao-primario" href="<?= e(url('cuidador.dia')) ?>">← Voltar ao Diário do Bebê</a>
</div>
