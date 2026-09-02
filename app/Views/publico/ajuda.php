<?php
// Tutorial de uso — página pública completa (sem layout), no design do sistema.
// Minimalista: cada papel tem os seus 3–5 passos essenciais, com telas reais.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F0">
    <meta name="description" content="Como usar o Diário do Bebê: guia rápido para a babá registrar o dia, para os pais acompanharem e para avós e pessoas queridas verem tudo em tempo real.">
    <title>Como usar — Diário do Bebê</title>
    <?= meta_og(
        'Como usar o Diário do Bebê',
        'Guia rápido por papel: babá registra o dia em 2 toques, pais acompanham e configuram, avós veem tudo em tempo real.'
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
        <h1 style="font-size:1.7rem">Como usar o Diário do Bebê</h1>
        <p class="landing-sub">Guia rápido para cada pessoa da família. Leva 2 minutos para ler —
            e o app foi feito para você quase não precisar dele.</p>
        <nav class="acoes-pagina" style="justify-content:center">
            <a class="botao botao-contorno botao-pequeno" href="#instalar">Instalar</a>
            <a class="botao botao-contorno botao-pequeno" href="#baba">Sou a babá</a>
            <a class="botao botao-contorno botao-pequeno" href="#pais">Sou pai/mãe</a>
            <a class="botao botao-contorno botao-pequeno" href="#avos">Sou avó/avô</a>
        </nav>
    </section>

    <!-- ── Instalar e entrar ─────────────────────────────── -->
    <section class="cartao tutorial-secao" id="instalar">
        <h3><?= icone_ui('casa', 18, '#3E6A64') ?> Antes de tudo: instale na tela inicial</h3>
        <p class="texto-apoio">O Diário do Bebê funciona direto no navegador — não precisa de loja
            de aplicativos. Instalando na tela inicial, ele abre como um app normal e
            <strong>funciona até sem internet</strong> (os registros sincronizam sozinhos depois).</p>
        <div class="tutorial-duplo">
            <div>
                <h4>📱 Android (Chrome)</h4>
                <ol class="tutorial-passos">
                    <li>Abra o endereço que você recebeu no convite.</li>
                    <li>Toque no menu <strong>⋮</strong> (canto superior direito).</li>
                    <li>Toque em <strong>"Adicionar à tela inicial"</strong> (ou "Instalar app").</li>
                </ol>
            </div>
            <div>
                <h4>🍎 iPhone (Safari)</h4>
                <ol class="tutorial-passos">
                    <li>Abra o endereço no <strong>Safari</strong>.</li>
                    <li>Toque no botão de <strong>compartilhar</strong> <small>(quadrado com seta ↑)</small>.</li>
                    <li>Role e toque em <strong>"Adicionar à Tela de Início"</strong>.</li>
                </ol>
            </div>
        </div>
        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/entrar.png')) ?>" alt="Tela de entrada do Diário do Bebê" loading="lazy">
            </figure>
            <div>
                <h4>Entrando no app</h4>
                <ol class="tutorial-passos">
                    <li>Toque no ícone do <strong>Diário do Bebê</strong> na tela inicial.</li>
                    <li>Digite o <strong>e-mail</strong> e a <strong>senha</strong> que você criou ao aceitar o convite.</li>
                    <li>Na primeira entrada, leia e aceite o termo de privacidade — cada pessoa tem o seu.</li>
                </ol>
                <p class="texto-apoio">Esqueceu a senha? Use o link "Esqueci minha senha" na própria tela.
                    Você só precisa entrar uma vez — o app continua conectado.</p>
            </div>
        </div>
    </section>

    <!-- ── Babá / cuidadora ──────────────────────────────── -->
    <section class="cartao tutorial-secao" id="baba">
        <h3><?= icone_ui('pessoa', 18, '#B05E3C') ?> Para a babá / cuidadora — registrar o dia</h3>
        <p class="texto-apoio">Seu trabalho no app se resume a isto: <strong>aconteceu, registrou</strong>.
            Dois toques e pronto — os pais veem na hora, e ninguém precisa mandar mensagem perguntando.</p>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/baba-meudia.png')) ?>" alt="Tela Meu Dia com a linha do tempo e atalhos" loading="lazy">
            </figure>
            <div>
                <h4>1 · A tela "Meu Dia"</h4>
                <ol class="tutorial-passos">
                    <li>Ao entrar, você já cai no <strong>Meu Dia</strong>: a linha do tempo de hoje.</li>
                    <li>Se os pais montaram um <strong>roteiro</strong>, os horários combinados aparecem
                        como cartões — é o seu guia do dia.</li>
                    <li>Os atalhos no rodapé registram o mais comum em um toque
                        (mamadeira, fralda, humor…).</li>
                </ol>
            </div>
        </div>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/baba-folha.png')) ?>" alt="Janela de registro com as categorias por grupo" loading="lazy">
            </figure>
            <div>
                <h4>2 · Registrar qualquer coisa</h4>
                <ol class="tutorial-passos">
                    <li>Toque no botão redondo <strong>+</strong> (canto inferior direito).</li>
                    <li>Abre a janela com tudo o que dá para registrar, por grupo:
                        alimentação, sono, higiene, saúde, rotina…</li>
                    <li>Toque no que aconteceu.</li>
                </ol>
            </div>
        </div>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/baba-form.png')) ?>" alt="Formulário de registro de mamadeira" loading="lazy">
            </figure>
            <div>
                <h4>3 · Preencher e salvar</h4>
                <ol class="tutorial-passos">
                    <li>Cada registro pede só o essencial (ex.: mamadeira → volume).</li>
                    <li>A hora já vem preenchida — ajuste se registrar depois.</li>
                    <li>Dá para <strong>ditar por voz</strong> a observação e <strong>anexar foto</strong>
                        quando fizer sentido.</li>
                    <li><strong>Salvar</strong>. Sem internet? Salva do mesmo jeito e envia sozinho depois.</li>
                </ol>
                <p class="texto-apoio">Errou algo? Nos primeiros minutos você mesma corrige; depois disso,
                    o app envia um <strong>pedido de correção</strong> para os pais aprovarem — ninguém
                    apaga nada às escondidas, e isso protege você também.</p>
            </div>
        </div>
        <p class="texto-apoio"><strong>Extras úteis:</strong> em <strong>Itens</strong> você avisa quando
            fralda/fórmula estão acabando; em <strong>Turnos</strong> o app marca sua chegada e saída
            (o primeiro registro do dia já abre o turno sozinho).</p>
    </section>

    <!-- ── Pais / responsáveis ───────────────────────────── -->
    <section class="cartao tutorial-secao" id="pais">
        <h3><?= icone_ui('olho', 18, '#3E6A64') ?> Para os pais / responsáveis — acompanhar e configurar</h3>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/pais-acompanhar.png')) ?>" alt="Tela Acompanhar com a linha do tempo do dia" loading="lazy">
            </figure>
            <div>
                <h4>1 · Acompanhar o dia</h4>
                <ol class="tutorial-passos">
                    <li>A tela <strong>Acompanhar</strong> mostra o dia em tempo real: mamadas, sonecas
                        e fraldas no topo, e a linha do tempo abaixo.</li>
                    <li>Ela <strong>atualiza sozinha</strong> — não precisa ficar puxando.</li>
                    <li>O semáforo avisa se o diário ficar muito tempo em silêncio.</li>
                    <li>Pendências chegam como cartões no topo: intercorrências para dar ciência,
                        correções para aprovar, medições do pediatra para confirmar.</li>
                </ol>
            </div>
        </div>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/pais-ficha.png')) ?>" alt="Ficha essencial da criança com medidas e percentis" loading="lazy">
            </figure>
            <div>
                <h4>2 · A ficha do bebê</h4>
                <ol class="tutorial-passos">
                    <li>Em Acompanhar, toque em <strong>"Dados de (nome)"</strong>.</li>
                    <li>Ali vive a ficha essencial: últimas medidas com percentil OMS,
                        alergias e saúde sempre à vista, curvas de crescimento, caderneta
                        de vacinas e histórico de consultas.</li>
                    <li><strong>Registrar medição</strong> guarda peso/altura da balança de casa —
                        cada medição vira um ponto na curva.</li>
                </ol>
            </div>
        </div>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/pais-qr.png')) ?>" alt="QR code da ficha para a consulta do pediatra" loading="lazy">
            </figure>
            <div>
                <h4>3 · Na consulta do pediatra</h4>
                <ol class="tutorial-passos">
                    <li>Na ficha do bebê, toque em <strong>"Ficha para consulta"</strong> e gere o link.</li>
                    <li>No consultório, toque em <strong>"Mostrar QR"</strong> e peça para o pediatra
                        apontar a câmera.</li>
                    <li>Ele vê a ficha completa (sem a rotina dia a dia e sem fotos) e devolve
                        peso, altura e vacinas direto para o app.</li>
                    <li>O que ele enviar chega para <strong>você confirmar</strong> — só então entra na curva.</li>
                </ol>
                <p class="texto-apoio">O link abre <strong>uma única vez</strong> e vale por 48 h — pode
                    gerar sem medo a cada consulta.</p>
            </div>
        </div>

        <div class="tutorial-linha">
            <figure class="tutorial-figura">
                <img src="<?= e(asset('img/tutorial/pais-usuarios.png')) ?>" alt="Tela de convite de pessoas da família" loading="lazy">
            </figure>
            <div>
                <h4>4 · Convidar quem cuida</h4>
                <ol class="tutorial-passos">
                    <li>Vá em <strong>Ajustes → Usuários</strong>.</li>
                    <li>Convide pelo e-mail, escolhendo o papel:
                        <strong>responsável</strong> (tudo), <strong>cuidador(a)</strong> (registra o dia)
                        ou <strong>leitor(a)</strong> (só acompanha — ideal para avós).</li>
                    <li>A pessoa recebe um link, cria a própria senha e pronto.</li>
                </ol>
                <p class="texto-apoio">Também em Ajustes: o <strong>Roteiro</strong> do dia (os horários
                    que guiam a babá), as categorias ativas e os alertas. Em <strong>Relatórios</strong>,
                    o resumo por dia, o modo pediatra e as exportações em PDF/CSV.</p>
            </div>
        </div>
    </section>

    <!-- ── Avós / leitores ───────────────────────────────── -->
    <section class="cartao tutorial-secao" id="avos">
        <h3><?= icone_ui('sorriso', 18, '#B05E3C') ?> Para avós e pessoas queridas — só acompanhar</h3>
        <ol class="tutorial-passos">
            <li>Aceite o convite recebido e crie a sua senha (uma vez só).</li>
            <li>Abra o app: a tela <strong>Acompanhar</strong> mostra o dia do bebê em tempo real —
                a mesma que os pais veem.</li>
            <li>Navegue pelos dias com as setinhas e veja a linha do tempo completa.</li>
            <li>Pronto — é só isso. 💚 Você <strong>vê tudo e não mexe em nada</strong>:
                nada quebra, nada apaga, sem susto.</li>
        </ol>
    </section>

    <section class="cartao tutorial-secao">
        <h3><?= icone_ui('coracao-pulso', 18, '#3E6A64') ?> Combinados de privacidade</h3>
        <p class="texto-apoio" style="margin-bottom:0">
            Cada família é um cofre separado — ninguém de fora vê nada. Sem anúncios e sem
            rastreadores. As fotos perdem a localização do celular antes de salvar. Nada é
            apagado às escondidas: correções passam pelos responsáveis e tudo fica no histórico.
            E os dados são da família: dá para exportar ou excluir tudo, a qualquer momento.
        </p>
    </section>

    <div class="acoes-pagina" style="justify-content:center">
        <a class="botao botao-primario botao-largo" href="<?= e(url('login')) ?>">Entrar no Diário do Bebê</a>
    </div>
</main>

<footer class="rodape">
    <p><?= e(APP_NOME) ?> — dúvidas? Fale com quem te convidou. 💚</p>
</footer>
</body>
</html>
