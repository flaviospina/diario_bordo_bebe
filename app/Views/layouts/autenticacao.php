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
    <meta name="theme-color" content="#FAF6F0">
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' — ' . APP_NOME : APP_NOME) ?></title>
    <?= meta_og(
        $ogTitulo ?? APP_NOME . ' — o dia inteirinho, registrado com carinho',
        $ogDescricao ?? 'O caderno da babá virou aplicativo: rotina do bebê em tempo real, roteiro do dia e ficha para o pediatra.',
        $ogImagem ?? 'img/og/capa.png'
    ) ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/favicon-64.png">
    <link rel="apple-touch-icon" href="<?= e(BASE_PATH) ?>/assets/img/icones/icone-192.png">
</head>
<body class="pagina-autenticacao">
<main class="cartao-autenticacao">
    <h1 class="marca-grande">
        <?= logo_marca(78) ?>
        <span>diário <span class="leve">do</span> bebê</span>
    </h1>
    <p class="frase-marca">o dia inteirinho, registrado com carinho</p>

    <?php foreach (Sessao::consumirFlashes() as $flash): ?>
        <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensagem']) ?></div>
    <?php endforeach; ?>

    <?= $conteudo ?>
</main>
</body>
</html>
