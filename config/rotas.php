<?php

declare(strict_types=1);

use App\Core\Roteador;

/**
 * Mapa central de rotas nomeadas (ver docs/planejamento.md §3.2).
 * Convenções: minúsculas, sem barra final, sem .php, sem query string em navegação.
 * Rotas de fases futuras apontam para Placeholder até a fase correspondente,
 * para que url() já funcione em todo o sistema desde a Fase 1.
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
    $r->get('/meu-dia', 'PlaceholderController@emDesenvolvimento')->nome('cuidador.dia')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/meu-dia/{data}', 'PlaceholderController@emDesenvolvimento')->nome('cuidador.dia.data')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registrar/{categoria}', 'PlaceholderController@emDesenvolvimento')->nome('registro.criar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registro/{codigo}', 'PlaceholderController@emDesenvolvimento')->nome('registro.ver')
        ->middleware('autenticado');
    $r->get('/registro/{codigo}/editar', 'PlaceholderController@emDesenvolvimento')->nome('registro.editar')
        ->middleware('autenticado', 'papel:cuidador,responsavel');
    $r->get('/registro/{codigo}/solicitar-alteracao', 'PlaceholderController@emDesenvolvimento')->nome('registro.solicitar')
        ->middleware('autenticado', 'papel:cuidador');

    // ── Pais (Fase 5) ──────────────────────────────────────────
    $r->get('/acompanhar', 'PlaceholderController@emDesenvolvimento')->nome('pais.acompanhar')
        ->middleware('autenticado', 'papel:responsavel,leitor');
    $r->get('/acompanhar/{data}', 'PlaceholderController@emDesenvolvimento')->nome('pais.acompanhar.data')
        ->middleware('autenticado', 'papel:responsavel,leitor');
    $r->get('/crianca/{slug}', 'PlaceholderController@emDesenvolvimento')->nome('crianca.ver')
        ->middleware('autenticado');
    $r->get('/crianca/{slug}/linha-do-tempo', 'PlaceholderController@emDesenvolvimento')->nome('crianca.timeline')
        ->middleware('autenticado');
    $r->get('/roteiro', 'PlaceholderController@emDesenvolvimento')->nome('roteiro.editar')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/intercorrencia/{codigo}', 'PlaceholderController@emDesenvolvimento')->nome('intercorrencia.ver')
        ->middleware('autenticado');
    $r->get('/solicitacoes', 'PlaceholderController@emDesenvolvimento')->nome('solicitacoes.lista')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/solicitacoes/{codigo}', 'PlaceholderController@emDesenvolvimento')->nome('solicitacoes.decidir')
        ->middleware('autenticado', 'papel:responsavel');

    // ── Relatórios (Fase 6) ────────────────────────────────────
    $r->get('/relatorios', 'PlaceholderController@emDesenvolvimento')->nome('relatorios.index')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/relatorios/resumo/{data}', 'PlaceholderController@emDesenvolvimento')->nome('relatorios.resumo')
        ->middleware('autenticado', 'papel:responsavel');
    $r->get('/relatorios/pediatra', 'PlaceholderController@emDesenvolvimento')->nome('relatorios.pediatra')
        ->middleware('autenticado', 'papel:responsavel');

    // ── Configurações (Fase 2) ─────────────────────────────────
    $r->get('/configuracoes', 'PlaceholderController@emDesenvolvimento')->nome('config.index')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/familia', 'PlaceholderController@emDesenvolvimento')->nome('config.familia')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/usuarios', 'PlaceholderController@emDesenvolvimento')->nome('config.usuarios')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/criancas', 'PlaceholderController@emDesenvolvimento')->nome('config.criancas')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/configuracoes/categorias', 'PlaceholderController@emDesenvolvimento')->nome('config.categorias')
        ->middleware('autenticado', 'papel:admin_familia');
    $r->get('/auditoria', 'PlaceholderController@emDesenvolvimento')->nome('auditoria.index')
        ->middleware('autenticado', 'papel:admin_familia');

    // ── Operação (fases 2–3) ───────────────────────────────────
    $r->get('/suprimentos', 'PlaceholderController@emDesenvolvimento')->nome('suprimentos.index')
        ->middleware('autenticado');
    $r->get('/turnos', 'PlaceholderController@emDesenvolvimento')->nome('turnos.index')
        ->middleware('autenticado');

    // ── Plataforma (Fase 6) ────────────────────────────────────
    $r->get('/painel', 'PlaceholderController@emDesenvolvimento')->nome('admin.painel')
        ->middleware('autenticado', 'papel:super_admin');

    // ── Arquivos protegidos (fases 3/6) ────────────────────────
    $r->get('/foto/{codigo}', 'PlaceholderController@emDesenvolvimento')->nome('foto.ver')
        ->middleware('autenticado');
    $r->get('/download/{codigo}', 'PlaceholderController@emDesenvolvimento')->nome('download.baixar')
        ->middleware('autenticado', 'papel:responsavel');
};
