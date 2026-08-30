<?php

declare(strict_types=1);

use App\Core\Roteador;

/**
 * Mapa central de rotas nomeadas (ver docs/planejamento.md §3.2).
 * Convenções: minúsculas, sem barra final, sem .php, sem query string em navegação.
 * Todas as fases entregues: cada rota aponta para seu controller definitivo.
 */
return static function (Roteador $r): void {

    // ── Público ────────────────────────────────────────────────
    $r->get('/', 'HomeController@inicio')->nome('home');

    $r->get('/entrar', 'AutenticacaoController@formularioEntrar')->nome('login');
    $r->post('/entrar', 'AutenticacaoController@entrar')->nome('login.enviar');
    $r->get('/sair', 'AutenticacaoController@sair')->nome('logout')->middleware('autenticado_basico');

    $r->get('/senha/recuperar', 'AutenticacaoController@formularioRecuperar')->nome('senha.recuperar');
    $r->post('/senha/recuperar', 'AutenticacaoController@recuperar')->nome('senha.recuperar.enviar');
    $r->get('/senha/redefinir/{token}', 'AutenticacaoController@formularioRedefinir')->nome('senha.redefinir');
    $r->post('/senha/redefinir/{token}', 'AutenticacaoController@redefinir')->nome('senha.redefinir.enviar');

    $r->get('/convite/{token}', 'ConviteController@mostrar')->nome('convite.aceitar');
    $r->post('/convite/{token}', 'ConviteController@aceitar')->nome('convite.aceitar.enviar');

    // ── LGPD (autenticado, mas SEM exigir termo — é aqui que ele é aceito) ──
    $r->get('/termos/{tipo}', 'LgpdController@termo')->nome('lgpd.termo')->middleware('autenticado_basico');
    $r->post('/termos/{tipo}', 'LgpdController@aceitar')->nome('lgpd.termo.aceitar')->middleware('autenticado_basico');

    // ── Cuidador (Fase 3) ──────────────────────────────────────
    $r->get('/meu-dia', 'CuidadorController@dia')->nome('cuidador.dia')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/meu-dia/{data}', 'CuidadorController@diaData')->nome('cuidador.dia.data')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registrar/{categoria}', 'RegistroController@criarForm')->nome('registro.criar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->post('/registrar/{categoria}', 'RegistroController@criarSalvar')->nome('registro.criar.salvar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registro/{codigo}', 'RegistroController@ver')->nome('registro.ver')
        ->middleware('autenticado');
    $r->get('/registro/{codigo}/editar', 'RegistroController@editarForm')->nome('registro.editar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->post('/registro/{codigo}/editar', 'RegistroController@editarSalvar')->nome('registro.editar.salvar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->post('/registro/{codigo}/excluir', 'RegistroController@excluir')->nome('registro.excluir')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registro/{codigo}/solicitar-alteracao', 'RegistroController@solicitarForm')->nome('registro.solicitar')
        ->middleware('autenticado', 'papel:cuidador');
    $r->post('/registro/{codigo}/solicitar-alteracao', 'RegistroController@solicitarEnviar')->nome('registro.solicitar.enviar')
        ->middleware('autenticado', 'papel:cuidador');

    // ── Pais (Fase 5) ──────────────────────────────────────────
    $r->get('/acompanhar', 'PaisController@acompanhar')->nome('pais.acompanhar')
        ->middleware('autenticado', 'papel:responsavel,leitor');
    $r->get('/acompanhar/{data}', 'PaisController@acompanharData')->nome('pais.acompanhar.data')
        ->middleware('autenticado', 'papel:responsavel,leitor');
    $r->get('/crianca/{slug}', 'CriancaController@ver')->nome('crianca.ver')
        ->middleware('autenticado');
    $r->get('/crianca/{slug}/linha-do-tempo', 'CriancaController@timeline')->nome('crianca.timeline')
        ->middleware('autenticado');
    $r->get('/roteiro', 'RoteiroController@editar')->nome('roteiro.editar')
        ->middleware('autenticado', 'papel:responsavel');
    $r->post('/roteiro', 'RoteiroController@salvar')->nome('roteiro.salvar')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/intercorrencia/{codigo}', 'IntercorrenciaController@ver')->nome('intercorrencia.ver')
        ->middleware('autenticado');
    $r->post('/intercorrencia/{codigo}', 'IntercorrenciaController@darCiencia')->nome('intercorrencia.ciencia')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/solicitacoes', 'SolicitacaoController@lista')->nome('solicitacoes.lista')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/solicitacoes/{codigo}', 'SolicitacaoController@decidirForm')->nome('solicitacoes.decidir')
        ->middleware('autenticado', 'papel:responsavel');
    $r->post('/solicitacoes/{codigo}', 'SolicitacaoController@decidir')->nome('solicitacoes.decidir.enviar')
        ->middleware('autenticado', 'papel:responsavel');

    // ── Relatórios (Fase 6) — exportações restritas a responsavel/admin ──
    $r->get('/relatorios', 'RelatorioController@index')->nome('relatorios.index')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/relatorios/resumo/{data}', 'RelatorioController@resumo')->nome('relatorios.resumo')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/relatorios/pediatra', 'RelatorioController@pediatra')->nome('relatorios.pediatra')
        ->middleware('autenticado', 'papel:responsavel');
    $r->post('/relatorios/exportar', 'RelatorioController@exportar')->nome('relatorios.exportar')
        ->middleware('autenticado', 'papel:responsavel');

    // ── Configurações (Fase 2) ─────────────────────────────────
    // O painel de configurações da família é acessível a admin_familia E
    // responsavel (seção 4 do escopo); gestão de usuários/crianças/categorias
    // fica restrita ao admin_familia (mapa de URLs).
    $r->get('/configuracoes', 'ConfiguracaoController@index')->nome('config.index')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/configuracoes/familia', 'ConfiguracaoController@familia')->nome('config.familia')
        ->middleware('autenticado', 'papel:responsavel');
    $r->post('/configuracoes/familia', 'ConfiguracaoController@salvarFamilia')->nome('config.familia.salvar')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/configuracoes/usuarios', 'ConfiguracaoController@usuarios')->nome('config.usuarios')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->post('/configuracoes/usuarios', 'ConfiguracaoController@acaoUsuarios')->nome('config.usuarios.acao')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/criancas', 'ConfiguracaoController@criancas')->nome('config.criancas')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->post('/configuracoes/criancas', 'ConfiguracaoController@salvarCrianca')->nome('config.criancas.salvar')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/categorias', 'ConfiguracaoController@categorias')->nome('config.categorias')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->post('/configuracoes/categorias', 'ConfiguracaoController@salvarCategorias')->nome('config.categorias.salvar')
        ->middleware('autenticado', 'papel:admin_familia');

    // Perfil complementar do próprio usuário (campos opcionais)
    $r->get('/perfil', 'PerfilController@editar')->nome('perfil.editar')->middleware('autenticado');
    $r->post('/perfil', 'PerfilController@salvar')->nome('perfil.salvar')->middleware('autenticado');
    $r->get('/auditoria', 'AuditoriaController@index')->nome('auditoria.index')
        ->middleware('autenticado', 'papel:admin_familia');

    // ── Operação (fases 2–3) ───────────────────────────────────
    $r->get('/suprimentos', 'SuprimentoController@index')->nome('suprimentos.index')
        ->middleware('autenticado');
    $r->post('/suprimentos', 'SuprimentoController@acao')->nome('suprimentos.acao')
        ->middleware('autenticado');
    $r->get('/turnos', 'TurnoController@index')->nome('turnos.index')
        ->middleware('autenticado');
    $r->post('/turnos', 'TurnoController@ajustar')->nome('turnos.acao')
        ->middleware('autenticado', 'papel:cuidador,responsavel');

    // ── Plataforma (Fase 6) ────────────────────────────────────
    $r->get('/painel', 'PainelAdminController@index')->nome('admin.painel')
        ->middleware('autenticado', 'papel:super_admin');
    $r->post('/painel', 'PainelAdminController@acao')->nome('admin.painel.acao')
        ->middleware('autenticado', 'papel:super_admin');

    // ── PWA (Fase 4): manifest e SW gerados por PHP p/ embutir BASE_PATH ──
    $r->get('/manifest.webmanifest', 'PwaController@manifest')->nome('pwa.manifest');
    $r->get('/sw.js', 'PwaController@serviceWorker')->nome('pwa.sw');
    $r->get('/offline', 'PwaController@offline')->nome('pwa.offline');

    // ── API (sessão + header X-CSRF) ───────────────────────────
    $r->post('/api/registros', 'Api\RegistroApiController@criar')->nome('api.registros.criar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->post('/api/sincronizar', 'Api\SincronizacaoApiController@sincronizar')->nome('api.sincronizar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/api/dia/{data}', 'Api\RegistroApiController@dia')->nome('api.dia')
        ->middleware('autenticado');

    // Autenticadas por token de serviço (sem sessão): cron/n8n
    $r->post('/api/tarefas/{tarefa}', 'Api\TarefaApiController@executar')->nome('api.tarefas');
    $r->post('/api/webhook/status', 'Api\WebhookApiController@status')->nome('api.webhook.status');

    // ── Arquivos protegidos (fases 3/6) ────────────────────────
    $r->get('/foto/{codigo}', 'ArquivoController@foto')->nome('foto.ver')
        ->middleware('autenticado');
    $r->get('/download/{codigo}', 'ArquivoController@download')->nome('download.baixar')
        ->middleware('autenticado', 'papel:responsavel');
};
