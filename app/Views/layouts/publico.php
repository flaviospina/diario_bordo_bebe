<?php

/** @var string $conteudo */
/** @var string $titulo */

// Layout público (ficha do pediatra): sem menu, sem manifest, sem sessão.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' — ' . APP_NOME : APP_NOME) ?></title>
    <?php // OG genérico e neutro: a prévia nunca expõe o nome da criança ?>
    <?= meta_og(
        'Ficha para consulta — ' . APP_NOME,
        'Link de uso único gerado pela família para a consulta com o pediatra.'
    ) ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/favicon-64.png">
</head>
<body class="pagina-publica">
<div class="bolha-decor" aria-hidden="true"></div>

<header class="topo">
    <div class="topo-interno">
        <span class="marca">
            <?= logo_marca(30) ?>
            <span class="marca-texto">diário <span class="leve">do</span> bebê</span>
        </span>
    </div>
</header>

<main class="conteudo conteudo-publico">
    <?= $conteudo ?>
</main>

<footer class="rodape">
    <p>Ficha gerada pelo <?= e(APP_NOME) ?> — o diário digital da rotina do bebê,
        preenchido pela família e pela pessoa cuidadora.</p>
</footer>
</body>
</html>
