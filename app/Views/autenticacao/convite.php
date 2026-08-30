<?php

use App\Core\Csrf;

/** @var array $convite */
/** @var string $token */

$nomesPapeis = [
    'admin_familia' => 'administrador(a) da família',
    'responsavel'   => 'responsável',
    'cuidador'      => 'cuidador(a)',
    'leitor'        => 'leitor(a)',
];
?>
<h2>Criar sua conta</h2>
<p class="texto-apoio">
    Você foi convidado(a) para a família <strong><?= e($convite['familia_nome']) ?></strong>
    como <strong><?= e($nomesPapeis[$convite['papel']] ?? $convite['papel']) ?></strong>.
</p>
<form method="post" action="<?= e(url('convite.aceitar.enviar', ['token' => $token])) ?>" class="formulario">
    <?= Csrf::campo() ?>

    <label for="email_convite">E-mail</label>
    <input type="email" id="email_convite" value="<?= e($convite['email']) ?>" disabled>

    <label for="nome">Seu nome</label>
    <input type="text" id="nome" name="nome" required maxlength="120" autocomplete="name" autofocus>

    <label for="telefone">WhatsApp (opcional)</label>
    <input type="tel" id="telefone" name="telefone" maxlength="20" autocomplete="tel" placeholder="(11) 99999-9999">

    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" required minlength="10" autocomplete="new-password">
    <small class="texto-apoio">Mínimo de 10 caracteres.</small>

    <button type="submit" class="botao botao-primario botao-largo">Criar conta</button>
</form>
