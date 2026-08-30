<?php

use App\Core\Sessao;

/** @var string $conteudo */
/** @var string $titulo */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2f6f6a">
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' — ' . APP_NOME : APP_NOME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="pagina-autenticacao">
<main class="cartao-autenticacao">
    <h1 class="marca-grande"><?= e(APP_NOME) ?></h1>

    <?php foreach (Sessao::consumirFlashes() as $flash): ?>
        <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensagem']) ?></div>
    <?php endforeach; ?>

    <?= $conteudo ?>
</main>
</body>
</html>
