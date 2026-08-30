<?php

use App\Core\Autenticacao;
use App\Core\Sessao;

/** @var string $conteudo */
/** @var string $titulo */

$usuario = Autenticacao::usuario();
$papel = $usuario['papel'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2f6f6a">
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' — ' . APP_NOME : APP_NOME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="manifest" href="<?= e(url('pwa.manifest')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/icone-192.png">
    <link rel="apple-touch-icon" href="<?= e(BASE_PATH) ?>/assets/img/icones/icone-192.png">
</head>
<body>
<a class="pular-conteudo" href="#conteudo">Ir para o conteúdo</a>

<header class="topo">
    <div class="topo-interno">
        <a class="marca" href="<?= e(url('home')) ?>"><?= e(APP_NOME) ?></a>
        <?php if ($usuario !== null): ?>
            <nav class="menu" aria-label="Menu principal">
                <?php if (in_array($papel, ['cuidador', 'responsavel', 'admin_familia'], true)): ?>
                    <a href="<?= e(url('cuidador.dia')) ?>">Meu Dia</a>
                <?php endif; ?>
                <?php if (in_array($papel, ['responsavel', 'admin_familia', 'leitor'], true)): ?>
                    <a href="<?= e(url('pais.acompanhar')) ?>">Acompanhar</a>
                <?php endif; ?>
                <?php if (in_array($papel, ['responsavel', 'admin_familia'], true)): ?>
                    <a href="<?= e(url('roteiro.editar')) ?>">Roteiro</a>
                    <a href="<?= e(url('relatorios.index')) ?>">Relatórios</a>
                    <a href="<?= e(url('solicitacoes.lista')) ?>">Solicitações</a>
                <?php endif; ?>
                <?php if ($papel !== 'super_admin'): ?>
                    <a href="<?= e(url('suprimentos.index')) ?>">Suprimentos</a>
                    <a href="<?= e(url('turnos.index')) ?>">Turnos</a>
                <?php endif; ?>
                <?php if (in_array($papel, ['responsavel', 'admin_familia'], true)): ?>
                    <a href="<?= e(url('config.index')) ?>">Configurações</a>
                <?php endif; ?>
                <?php if ($papel === 'super_admin'): ?>
                    <a href="<?= e(url('admin.painel')) ?>">Painel</a>
                <?php endif; ?>
            </nav>
            <div class="usuario-area">
                <span class="usuario-nome"><?= e($usuario['nome']) ?></span>
                <a class="botao botao-secundario botao-pequeno" href="<?= e(url('logout')) ?>">Sair</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<main id="conteudo" class="conteudo">
    <?php foreach (Sessao::consumirFlashes() as $flash): ?>
        <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensagem']) ?></div>
    <?php endforeach; ?>

    <?= $conteudo ?>
</main>

<footer class="rodape">
    <p><?= e(APP_NOME) ?> · v<?= e(APP_VERSAO) ?></p>
</footer>

<script>
    // Única ponte PHP → JS permitida para URLs: base e mapa de rotas nomeadas.
    window.APP = <?= json_encode([
        'basePath' => BASE_PATH,
        'rotas'    => $GLOBALS['__roteador']->mapaParaJs(),
        'csrf'     => $usuario !== null ? \App\Core\Csrf::token() : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<script src="<?= e(asset('js/base.js')) ?>"></script>
<?php if ($usuario !== null): ?>
    <script src="<?= e(asset('js/offline.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
