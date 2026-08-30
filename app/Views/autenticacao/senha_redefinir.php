<?php

use App\Core\Csrf;

/** @var string $token */
?>
<h2>Criar nova senha</h2>
<form method="post" action="<?= e(url('senha.redefinir.enviar', ['token' => $token])) ?>" class="formulario">
    <?= Csrf::campo() ?>
    <label for="senha">Nova senha</label>
    <input type="password" id="senha" name="senha" required minlength="10" autocomplete="new-password" autofocus>
    <small class="texto-apoio">Mínimo de 10 caracteres.</small>

    <label for="senha_confirmacao">Repita a nova senha</label>
    <input type="password" id="senha_confirmacao" name="senha_confirmacao" required minlength="10" autocomplete="new-password">

    <button type="submit" class="botao botao-primario botao-largo">Salvar nova senha</button>
</form>
