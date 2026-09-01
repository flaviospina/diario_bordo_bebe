<?php

use App\Core\Autenticacao;
use App\Core\Sessao;

/** @var string $conteudo */
/** @var string $titulo */

$usuario = Autenticacao::usuario();
$papel = $usuario['papel'] ?? '';

// Caminho atual (sem BASE_PATH) para marcar a aba ativa
$caminhoAtual = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (BASE_PATH !== '' && str_starts_with($caminhoAtual, BASE_PATH)) {
    $caminhoAtual = substr($caminhoAtual, strlen(BASE_PATH)) ?: '/';
}

// Abas inferiores (mobile) por papel: 4 destinos essenciais de cada rotina
$abas = [];
$badgeSolicitacoes = 0;
if ($usuario !== null) {
    if ($papel === 'cuidador') {
        $abas = [
            ['rota' => 'cuidador.dia', 'rotulo' => 'Meu Dia', 'icone' => 'casa'],
            ['rota' => 'turnos.index', 'rotulo' => 'Turnos', 'icone' => 'relogio'],
            ['rota' => 'suprimentos.index', 'rotulo' => 'Itens', 'icone' => 'cesto'],
            ['rota' => 'perfil.editar', 'rotulo' => 'Perfil', 'icone' => 'pessoa'],
        ];
    } elseif (in_array($papel, ['responsavel', 'admin_familia'], true)) {
        try {
            $badgeSolicitacoes = (new \App\Repositories\RepositorioSolicitacoes())->contarPendentes();
        } catch (\Throwable) {
            $badgeSolicitacoes = 0;
        }
        $abas = [
            ['rota' => 'pais.acompanhar', 'rotulo' => 'Acompanhar', 'icone' => 'olho'],
            ['rota' => 'relatorios.index', 'rotulo' => 'Relatórios', 'icone' => 'grafico'],
            ['rota' => 'solicitacoes.lista', 'rotulo' => 'Solicitações', 'icone' => 'balao', 'badge' => $badgeSolicitacoes],
            ['rota' => 'config.index', 'rotulo' => 'Ajustes', 'icone' => 'engrenagem'],
        ];
    } elseif ($papel === 'leitor') {
        $abas = [
            ['rota' => 'pais.acompanhar', 'rotulo' => 'Acompanhar', 'icone' => 'olho'],
            ['rota' => 'suprimentos.index', 'rotulo' => 'Itens', 'icone' => 'cesto'],
            ['rota' => 'turnos.index', 'rotulo' => 'Turnos', 'icone' => 'relogio'],
            ['rota' => 'perfil.editar', 'rotulo' => 'Perfil', 'icone' => 'pessoa'],
        ];
    } elseif ($papel === 'super_admin') {
        $abas = [
            ['rota' => 'admin.painel', 'rotulo' => 'Painel', 'icone' => 'engrenagem'],
            ['rota' => 'perfil.editar', 'rotulo' => 'Perfil', 'icone' => 'pessoa'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F0">
    <title><?= e(($titulo ?? '') !== '' ? $titulo . ' — ' . APP_NOME : APP_NOME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="manifest" href="<?= e(url('pwa.manifest')) ?>">
    <link rel="icon" type="image/png" href="<?= e(BASE_PATH) ?>/assets/img/icones/favicon-64.png">
    <link rel="apple-touch-icon" href="<?= e(BASE_PATH) ?>/assets/img/icones/icone-192.png">
</head>
<body>
<a class="pular-conteudo" href="#conteudo">Ir para o conteúdo</a>
<div class="bolha-decor" aria-hidden="true"></div>

<header class="topo">
    <div class="topo-interno">
        <a class="marca" href="<?= e(url('home')) ?>">
            <?= logo_marca(30) ?>
            <span class="marca-texto">diário <span class="leve">do</span> bebê</span>
        </a>
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
                    <a href="<?= e(url('config.index')) ?>">Configurações</a>
                <?php endif; ?>
                <?php if ($papel !== 'super_admin'): ?>
                    <a href="<?= e(url('suprimentos.index')) ?>">Suprimentos</a>
                    <a href="<?= e(url('turnos.index')) ?>">Turnos</a>
                <?php endif; ?>
                <?php if ($papel === 'super_admin'): ?>
                    <a href="<?= e(url('admin.painel')) ?>">Painel</a>
                <?php endif; ?>
            </nav>
            <div class="usuario-area">
                <span class="usuario-nome"><?= e($usuario['nome']) ?></span>
                <a class="botao botao-contorno botao-pequeno" href="<?= e(url('logout')) ?>">Sair</a>
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

<?php if ($abas !== []): ?>
    <nav class="nav-abas" aria-label="Navegação principal">
        <?php foreach ($abas as $aba): ?>
            <?php
            $destino = url($aba['rota']);
            $caminhoAba = BASE_PATH !== '' ? substr($destino, strlen(BASE_PATH)) : $destino;
            $ativa = $caminhoAba === '/' ? $caminhoAtual === '/' : str_starts_with($caminhoAtual, $caminhoAba);
            ?>
            <a class="aba-item <?= $ativa ? 'aba-ativa' : '' ?>" href="<?= e($destino) ?>"
               <?= $ativa ? 'aria-current="page"' : '' ?>>
                <span class="selo-aba"><?= icone_ui($aba['icone'], 22, 'currentColor', 2.0) ?></span>
                <span><?= e($aba['rotulo']) ?></span>
                <?php if (!empty($aba['badge'])): ?>
                    <span class="aba-badge"><?= (int)$aba['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

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
