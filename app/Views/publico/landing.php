<?php

use App\Core\Csrf;
use App\Core\Sessao;

// Landing pública — página completa (sem layout): visitante ainda não tem sessão.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F0">
    <meta name="description" content="O caderno da babá virou app: a rotina do bebê registrada em tempo real pela família e por quem cuida — com roteiro do dia, curvas de crescimento e ficha para o pediatra.">
    <title>Diário do Bebê — o dia inteirinho, registrado com carinho</title>
    <?= meta_og(
        'Diário do Bebê — o dia inteirinho, registrado com carinho',
        'O caderno da babá virou aplicativo: rotina em tempo real, roteiro do dia, curvas de crescimento e ficha para o pediatra.'
    ) ?>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/favicon-64.png">
    <link rel="apple-touch-icon" href="<?= e(BASE_PATH) ?>/assets/img/icones/icone-192.png">
</head>
<body class="pagina-publica">
<div class="bolha-decor" aria-hidden="true"></div>

<header class="topo">
    <div class="topo-interno">
        <span class="marca">
            <?= logo_marca(30) ?>
            <span class="marca-texto">diário <span class="leve">do</span> bebê</span>
        </span>
        <div class="usuario-area">
            <a class="botao botao-contorno botao-pequeno" href="<?= e(url('login')) ?>">Entrar</a>
        </div>
    </div>
</header>

<main class="conteudo conteudo-publico">
    <?php foreach (Sessao::consumirFlashes() as $flash): ?>
        <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensagem']) ?></div>
    <?php endforeach; ?>

    <section class="landing-hero">
        <?= logo_marca(84) ?>
        <h1>O caderno da babá<br>virou aplicativo</h1>
        <p class="landing-sub">A rotina do seu bebê — mamadas, sonecas, fraldas, medicações —
            registrada em tempo real por quem cuida e acompanhada por você, de onde estiver.</p>
        <div class="landing-ctas">
            <a class="botao botao-primario botao-largo" href="#convite">Quero um convite</a>
            <a class="botao botao-contorno" href="<?= e(url('login')) ?>">Já uso — entrar</a>
        </div>
        <p class="texto-apoio">Em fase de <strong>famílias fundadoras</strong>: acesso completo, gratuito, por convite.</p>
    </section>

    <section class="landing-grade">
        <div class="cartao landing-recurso">
            <span class="selo-categoria landing-selo landing-selo-salvia"><?= icone_ui('olho', 22, '#3E6A64') ?></span>
            <h3>Acompanhe em tempo real</h3>
            <p class="texto-apoio">Cada registro da babá ou da família aparece na hora no seu celular,
                organizado na linha do tempo do dia — com alerta se o diário ficar em silêncio.</p>
        </div>
        <div class="cartao landing-recurso">
            <span class="selo-categoria landing-selo landing-selo-pessego"><?= icone_ui('rotina', 22, '#B05E3C') ?></span>
            <h3>Roteiro do dia</h3>
            <p class="texto-apoio">Vocês prescrevem a rotina — horários de mamada, soneca, banho — e o app
                mostra o que foi feito, o que atrasou e o que ficou para depois.</p>
        </div>
        <div class="cartao landing-recurso">
            <span class="selo-categoria landing-selo landing-selo-salvia"><?= icone_ui('estetoscopio', 22, '#3E6A64') ?></span>
            <h3>Ficha para o pediatra</h3>
            <p class="texto-apoio">Na consulta, um QR code de uso único abre a ficha essencial: curvas de
                crescimento OMS, vacinas, alergias e o resumo dos últimos 30 dias. O médico devolve
                as medidas direto para o app.</p>
        </div>
        <div class="cartao landing-recurso">
            <span class="selo-categoria landing-selo landing-selo-pessego"><?= icone_ui('coracao-pulso', 22, '#B05E3C') ?></span>
            <h3>Dados da família, e de mais ninguém</h3>
            <p class="texto-apoio">Sem anúncios, sem rastreadores, sem análise de terceiros. Fotos sem
                localização, exportação completa e exclusão definitiva quando quiserem — LGPD de verdade.</p>
        </div>
    </section>

    <section class="cartao landing-passos">
        <h3>Como funciona</h3>
        <ol>
            <li><strong>Recebam o convite</strong> — criem a família em 1 minuto e cadastrem o bebê.</li>
            <li><strong>Convidem quem cuida</strong> — o outro responsável, a babá, os avós: cada um com o seu acesso.</li>
            <li><strong>Vivam o dia</strong> — registros em 2 toques (funciona até sem internet) e vocês acompanhando tudo.</li>
        </ol>
        <div class="acoes-pagina">
            <a class="botao botao-contorno botao-pequeno" href="<?= e(url('ajuda')) ?>">
                <?= icone_ui('livro', 15, 'currentColor', 2.0) ?> Ver o tutorial completo</a>
        </div>
    </section>

    <section class="cartao landing-convite" id="convite">
        <h3><?= icone_ui('balao', 20, '#3E6A64') ?> Quero um convite</h3>
        <p class="texto-apoio">Estamos abrindo por convites para cuidar bem de cada família.
            Deixe seu contato — os primeiros entram como <strong>família fundadora</strong>,
            com acesso completo e gratuito.</p>
        <form method="post" action="<?= e(url('lista.espera')) ?>" class="formulario">
            <?= Csrf::campo() ?>
            <div class="linha-campos">
                <div>
                    <label for="nome">Seu nome</label>
                    <input type="text" id="nome" name="nome" required maxlength="120">
                </div>
                <div>
                    <label for="email">Seu e-mail</label>
                    <input type="email" id="email" name="email" required maxlength="190">
                </div>
            </div>
            <label for="whatsapp">WhatsApp <small>(opcional — respondemos mais rápido)</small></label>
            <input type="tel" id="whatsapp" name="whatsapp" maxlength="30" placeholder="(11) 99999-9999">
            <label for="mensagem">Conte rapidinho sobre vocês <small>(opcional)</small></label>
            <textarea id="mensagem" name="mensagem" rows="2" maxlength="500"
                      placeholder="ex.: bebê de 4 meses, babá começa mês que vem"></textarea>
            <button type="submit" class="botao botao-primario botao-largo">Entrar na lista de convites</button>
        </form>
    </section>
</main>

<footer class="rodape">
    <p><?= e(APP_NOME) ?> — feito por pais, para famílias.<br>
        Sem anúncios e sem rastreadores; os dados são seus, sempre.</p>
</footer>
</body>
</html>
