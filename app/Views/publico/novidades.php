<?php
/** @var array $novidades */
// Novidades — página pública no mesmo espírito da /ajuda: o e-mail conta o
// macro, aqui mora o detalhe de como cada funcionalidade funciona.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F0">
    <meta name="description" content="O que há de novo no Diário do Bebê — cada funcionalidade nova, explicada em detalhes.">
    <title>Novidades — Diário do Bebê</title>
    <?= meta_og(
        'Novidades do Diário do Bebê',
        'O que há de novo no app — cada funcionalidade explicada em detalhes, do jeito que a família usa.'
    ) ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/favicon-64.png">
</head>
<body class="pagina-publica">
<div class="bolha-decor" aria-hidden="true"></div>

<header class="topo">
    <div class="topo-interno">
        <a class="marca" href="<?= e(url('home')) ?>">
            <?= logo_marca(30) ?>
            <span class="marca-texto">diário <span class="leve">do</span> bebê</span>
        </a>
        <div class="usuario-area">
            <a class="botao botao-contorno botao-pequeno" href="<?= e(url('login')) ?>">Entrar</a>
        </div>
    </div>
</header>

<main class="conteudo conteudo-publico">
    <section class="landing-hero" style="padding-bottom:.4rem">
        <h1 style="font-size:1.7rem">Novidades</h1>
        <p class="landing-sub">O Diário do Bebê evolui com as famílias fundadoras.
            Cada novidade chega aqui explicada em detalhes — e no app, sem instalar nada.</p>
    </section>

    <?php if ($novidades === []): ?>
        <section class="cartao tutorial-secao">
            <p class="texto-apoio" style="margin:0">Ainda não há novidades publicadas — em breve tem coisa boa por aqui. 💚</p>
        </section>
    <?php endif; ?>

    <?php foreach ($novidades as $novidade): ?>
        <section class="cartao tutorial-secao" id="<?= e($novidade['slug']) ?>">
            <p class="novidade-data"><?= e(data_br($novidade['criado_em'], 'd/m/Y')) ?></p>
            <h3><?= icone_ui('estrela', 18, '#B05E3C') ?> <?= e($novidade['titulo']) ?></h3>
            <p class="texto-apoio" style="font-weight:700; color:var(--tinta)"><?= e($novidade['resumo']) ?></p>
            <div class="novidade-detalhes">
                <?php foreach (preg_split('/\R{2,}/', (string)$novidade['detalhes']) ?: [] as $paragrafo): ?>
                    <p><?= nl2br(e(trim($paragrafo))) ?></p>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="acoes-pagina" style="justify-content:center">
        <a class="botao botao-primario" href="<?= e(url('login')) ?>">Entrar no Diário do Bebê</a>
        <a class="botao botao-contorno" href="<?= e(url('ajuda')) ?>">Como usar o app</a>
    </div>
</main>

<footer class="rodape">
    <p>Diário do Bebê — sugestões de novidade? Fale com a gente. 💚</p>
</footer>
</body>
</html>
