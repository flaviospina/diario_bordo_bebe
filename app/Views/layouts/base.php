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

// Aviso de novidades: no primeiro login após uma publicação, o usuário vê o
// resumo e confirma com "Entendi" (registrado em novidades_vistas). A sessão
// guarda a confirmação para não consultar o banco a cada página.
$novidadesPendentes = [];
if ($usuario !== null && $papel !== 'super_admin' && Sessao::obter('_novidades_confirmadas') === null) {
    try {
        $novidadesPendentes = (new \App\Repositories\RepositorioNovidades())
            ->naoVistasPorUsuario((int)$usuario['id']);
        if ($novidadesPendentes === []) {
            Sessao::definir('_novidades_confirmadas', true);
        }
    } catch (\Throwable) {
        $novidadesPendentes = []; // tabela ainda não migrada — nunca derruba a página
    }
}

// Portal da Colaboradora (Rodada 7): área separada do diário — cabeçalho e
// abas próprios, botão "Voltar ao Diário do Bebê", sem o "+" de registro.
$portal = isset($portal) && in_array($portal, ['colaboradora', 'gestao'], true) ? $portal : null;

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

<?php if ($portal !== null): ?>
    <?php
    $abas = $portal === 'colaboradora'
        ? [
            ['rota' => 'portal', 'rotulo' => 'Pagamentos', 'icone' => 'documento'],
            ['rota' => 'portal.documentos', 'rotulo' => 'Documentos', 'icone' => 'lupa'],
            ['rota' => 'portal.vt', 'rotulo' => 'Transporte', 'icone' => 'carrinho'],
            ['rota' => 'cuidador.dia', 'rotulo' => 'Diário', 'icone' => 'casa'],
        ]
        : [
            ['rota' => 'portal.gestao', 'rotulo' => 'Gestão', 'icone' => 'documento'],
            ['rota' => 'portal.gestao.novo', 'rotulo' => 'Importar', 'icone' => 'mais'],
            ['rota' => 'pais.acompanhar', 'rotulo' => 'Diário', 'icone' => 'casa'],
        ];
    ?>
<header class="topo topo-portal">
    <div class="topo-interno">
        <a class="marca" href="<?= e(url($portal === 'colaboradora' ? 'portal' : 'portal.gestao')) ?>">
            <?= logo_marca(30) ?>
            <span class="marca-texto">portal <span class="leve">da</span> colaboradora</span>
        </a>
        <?php if ($usuario !== null): ?>
            <nav class="menu" aria-label="Menu do portal">
                <?php if ($portal === 'colaboradora'): ?>
                    <a href="<?= e(url('portal')) ?>">Meus Pagamentos</a>
                    <a href="<?= e(url('portal.documentos')) ?>">Documentos</a>
                    <a href="<?= e(url('portal.vt')) ?>">Vale-transporte</a>
                <?php else: ?>
                    <a href="<?= e(url('portal.gestao')) ?>">Gestão</a>
                    <a href="<?= e(url('portal.gestao.novo')) ?>">Importar documento</a>
                <?php endif; ?>
            </nav>
            <div class="usuario-area">
                <a class="botao botao-secundario botao-pequeno botao-voltar-diario"
                   href="<?= e(url($papel === 'cuidador' ? 'cuidador.dia' : 'pais.acompanhar')) ?>">
                    <?= icone_ui('seta-esq', 15, 'currentColor', 2.4) ?> Voltar ao Diário do Bebê</a>
                <span class="usuario-nome"><?= e($usuario['nome']) ?></span>
                <a class="botao botao-contorno botao-pequeno" href="<?= e(url('logout')) ?>">Sair</a>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php else: ?>
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
                    <a href="<?= e(url('compras')) ?>">Compras</a>
                    <a href="<?= e(url('turnos.index')) ?>">Turnos</a>
                <?php endif; ?>
                <?php if ($papel === 'cuidador'): ?>
                    <a href="<?= e(url('portal')) ?>" class="menu-portal">Meus pagamentos</a>
                <?php elseif ($papel === 'admin_familia'): ?>
                    <a href="<?= e(url('portal.gestao')) ?>" class="menu-portal">Colaboradora</a>
                <?php endif; ?>
                <?php if ($papel === 'super_admin'): ?>
                    <a href="<?= e(url('admin.painel')) ?>">Painel</a>
                <?php endif; ?>
            </nav>
            <div class="usuario-area">
                <?php if ($papel !== 'super_admin'): ?>
                    <?php
                    $avisosNaoLidos = 0;
                    try {
                        $avisosNaoLidos = (new \App\Repositories\RepositorioAvisos())->contarNaoLidos((int)$usuario['id']);
                    } catch (\Throwable) {
                        $avisosNaoLidos = 0; // tabela ainda não migrada
                    }
                    ?>
                    <a class="botao-ajuda botao-sino" href="<?= e(url('avisos.index')) ?>" title="Avisos"
                       aria-label="Avisos<?= $avisosNaoLidos > 0 ? ' (' . $avisosNaoLidos . ' novos)' : '' ?>">
                        <?= icone_ui('balao', 17, 'currentColor', 2.0) ?>
                        <?php if ($avisosNaoLidos > 0): ?>
                            <span class="sino-badge"><?= $avisosNaoLidos > 9 ? '9+' : $avisosNaoLidos ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a class="botao-ajuda" href="<?= e(url('ajuda')) ?>" title="Como usar o app"
                   aria-label="Como usar o app"><?= icone_ui('livro', 17, 'currentColor', 2.0) ?></a>
                <span class="usuario-nome"><?= e($usuario['nome']) ?></span>
                <a class="botao botao-contorno botao-pequeno" href="<?= e(url('logout')) ?>">Sair</a>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>

<main id="conteudo" class="conteudo">
    <?php foreach (Sessao::consumirFlashes() as $flash): ?>
        <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="alert"><?= e($flash['mensagem']) ?></div>
    <?php endforeach; ?>

    <?= $conteudo ?>
</main>

<?php
// Botão flutuante de registro em QUALQUER tela (Rodada 4): quem registra
// (cuidador e responsáveis) sempre tem o "+" à mão; o Meu Dia e os
// formulários de registro já têm o próprio botão/fluxo, então ficam de fora.
$podeRegistrar = in_array($papel, ['cuidador', 'responsavel', 'admin_familia'], true);
$telaDeRegistro = str_starts_with($caminhoAtual, '/meu-dia') || str_starts_with($caminhoAtual, '/registrar');
if ($usuario !== null && $podeRegistrar && !$telaDeRegistro && $portal === null): ?>
    <a class="fab-registrar fab-global" href="<?= e(url('cuidador.dia')) ?>?registrar=1"
       aria-label="Registrar agora" title="Registrar agora">
        <?= icone_ui('mais', 26, '#FFFFFF', 2.8) ?>
    </a>
<?php endif; ?>

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
    <p><a href="<?= e(url('ajuda')) ?>">Como usar</a> · <a href="<?= e(url('novidades')) ?>">Novidades</a></p>
    <p><?= e(APP_NOME) ?> · v<?= e(APP_VERSAO) ?></p>
</footer>

<?php if ($novidadesPendentes !== []): ?>
    <div class="aviso-novidades" role="dialog" aria-modal="true" aria-labelledby="aviso-novidades-titulo">
        <div class="aviso-novidades-caixa">
            <span class="selo-categoria selo-aviso-novidades"><?= icone_ui('estrela', 24, '#B05E3C') ?></span>
            <h3 id="aviso-novidades-titulo">Novidades no Diário do Bebê 🎉</h3>
            <p class="texto-apoio" style="text-align:center; margin:0 0 .6rem">
                Desde a sua última visita, o app ganhou
                <?= count($novidadesPendentes) === 1 ? 'esta melhoria' : 'estas melhorias' ?>:
            </p>
            <?php foreach ($novidadesPendentes as $novidade): ?>
                <div class="aviso-novidade-item">
                    <strong><?= e($novidade['titulo']) ?></strong>
                    <p><?= e($novidade['resumo']) ?></p>
                    <a href="<?= e(url('novidades')) ?>#<?= e($novidade['slug']) ?>">Ver como funciona →</a>
                </div>
            <?php endforeach; ?>
            <form method="post" action="<?= e(url('novidades.vistas')) ?>">
                <?= \App\Core\Csrf::campo() ?>
                <?php foreach ($novidadesPendentes as $novidade): ?>
                    <input type="hidden" name="novidade_ids[]" value="<?= (int)$novidade['id'] ?>">
                <?php endforeach; ?>
                <input type="hidden" name="voltar" value="<?= e($caminhoAtual) ?>">
                <button type="submit" class="botao botao-primario botao-largo">Entendi, vamos lá</button>
            </form>
            <p class="texto-apoio" style="text-align:center; margin:.6rem 0 0">
                <a href="<?= e(url('ajuda')) ?>">Ver o tutorial completo do app</a>
            </p>
        </div>
    </div>
<?php endif; ?>

<script nonce="<?= e(\App\Core\Resposta::nonce()) ?>">
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
