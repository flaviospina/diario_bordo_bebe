<?php

use App\Core\Csrf;

/** @var string $codigo */
/** @var array $convite */
?>
<h2>Criar a sua família</h2>
<p class="texto-apoio">
    Vocês foram convidados como <strong>família fundadora</strong> do Diário do Bebê —
    acesso completo e gratuito. Leva 1 minuto:
</p>
<form method="post" action="<?= e(url('comecar.enviar', ['codigo' => $codigo])) ?>" class="formulario">
    <?= Csrf::campo() ?>

    <label for="familia">Nome da família</label>
    <input type="text" id="familia" name="familia" required maxlength="120" autofocus
           placeholder="ex.: Família Souza">

    <label for="nome">Seu nome</label>
    <input type="text" id="nome" name="nome" required maxlength="120" autocomplete="name">

    <label for="email">Seu e-mail</label>
    <input type="email" id="email" name="email" required maxlength="190" autocomplete="email">

    <label for="telefone">WhatsApp (opcional)</label>
    <input type="tel" id="telefone" name="telefone" maxlength="20" autocomplete="tel" placeholder="(11) 99999-9999">

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required minlength="10" autocomplete="new-password">
    <small class="texto-apoio">Mínimo de 10 caracteres.</small>

    <label class="caixa-selecao">
        <input type="checkbox" name="aceite" value="sim" required>
        <span>Li e aceito o termo de uso e a política de privacidade do Diário do Bebê
            (sem anúncios, sem rastreadores; os dados são da família e podem ser
            exportados ou apagados a qualquer momento). O termo completo fica
            disponível dentro do aplicativo.</span>
    </label>

    <button type="submit" class="botao botao-primario botao-largo">Criar família e começar</button>
</form>
<p class="texto-apoio" style="text-align:center">
    Depois é só cadastrar o bebê e convidar quem cuida: o outro responsável,
    a babá, os avós.
</p>
