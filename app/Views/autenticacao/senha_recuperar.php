<?php

use App\Core\Csrf;
?>
<h2>Recuperar senha</h2>
<p class="texto-apoio">Informe o e-mail da sua conta. Enviaremos um link para você criar uma nova senha.</p>
<form method="post" action="<?= e(url('senha.recuperar.enviar')) ?>" class="formulario">
    <?= Csrf::campo() ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required autocomplete="email" autofocus>

    <button type="submit" class="botao botao-primario botao-largo">Enviar link</button>
</form>
<p class="texto-apoio">
    <a href="<?= e(url('login')) ?>">Voltar para o login</a>
</p>
