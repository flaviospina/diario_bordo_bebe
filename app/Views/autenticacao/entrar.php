<?php

use App\Core\Csrf;
?>
<h2>Entrar</h2>
<form method="post" action="<?= e(url('login.enviar')) ?>" class="formulario">
    <?= Csrf::campo() ?>
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" required autocomplete="email" autofocus>

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required autocomplete="current-password">

    <button type="submit" class="botao botao-primario botao-largo">Entrar</button>
</form>
<p class="texto-apoio">
    <a href="<?= e(url('senha.recuperar')) ?>">Esqueci minha senha</a>
    · <a href="<?= e(url('ajuda')) ?>">Como usar o app</a>
</p>
