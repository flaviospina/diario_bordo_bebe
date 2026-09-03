<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioConvitesFamilia;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioListaEspera;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioPlanos;
use App\Services\ServicoPlataforma;

/**
 * Painel da plataforma (super_admin): famílias, planos, convites de família
 * fundadora e lista de espera. O super_admin nunca vê conteúdo de registros.
 */
final class PainelAdminController
{
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('painel/index', [
            'titulo' => 'Painel da plataforma',
            'familias' => (new RepositorioFamilias())->listarTodas(),
            'planos' => (new RepositorioPlanos())->ativos(),
            'convitesFamilia' => (new RepositorioConvitesFamilia())->listarRecentes(),
            'listaEspera' => (new RepositorioListaEspera())->listar(),
            'emails' => (new \App\Repositories\RepositorioEmails())->listar(20),
            'novidades' => (new \App\Repositories\RepositorioNovidades())->listarTodas(),
            'linkConvite' => Sessao::obter('_link_convite_plataforma'),
        ]);
    }

    public function acao(Requisicao $requisicao): void
    {
        Sessao::remover('_link_convite_plataforma');
        $familias = new RepositorioFamilias();
        $servico = new ServicoPlataforma();
        $log = new RepositorioLogAcessos();
        $planos = new RepositorioPlanos();
        $acao = (string)$requisicao->post('acao', '');

        $planoValido = static function (string $chave) use ($planos): string {
            return $planos->porChave($chave) !== null ? $chave : 'fundador';
        };

        if ($acao === 'criar_familia') {
            $resultado = $servico->criarFamiliaComConvite(
                (string)$requisicao->post('nome', ''),
                $planoValido((string)$requisicao->post('plano', 'fundador')),
                (string)$requisicao->post('email_admin', '')
            );
            if ($resultado['erro'] !== null) {
                Sessao::flash('erro', $resultado['erro']);
            } else {
                Sessao::definir('_link_convite_plataforma', $resultado['link']);
                Sessao::flash('sucesso', 'Família criada; convite do administrador enviado por e-mail.');
            }
            Resposta::redirecionarRota('admin.painel');
        }

        // ── Convites de família fundadora (auto-cadastro) ─────
        if ($acao === 'convite_familia') {
            $codigo = (new RepositorioConvitesFamilia())->criar(
                $planoValido((string)$requisicao->post('plano', 'fundador')),
                Autenticacao::id(),
                max(1, min(30, (int)$requisicao->post('validade_dias', '7'))),
                trim((string)$requisicao->post('observacao', '')) ?: null
            );
            Sessao::definir('_link_convite_plataforma', url_absoluta('comecar', ['codigo' => $codigo]));
            Sessao::flash('sucesso', 'Convite de família gerado! Copie o link e envie pelo WhatsApp.');
            $log->registrar(null, Autenticacao::id(), 'plataforma_convite_familia', 'convites_familia', null, $requisicao->ip());
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'revogar_convite_familia') {
            (new RepositorioConvitesFamilia())->revogar((int)$requisicao->post('convite_id', '0'));
            Sessao::flash('sucesso', 'Convite revogado.');
            Resposta::redirecionarRota('admin.painel');
        }

        // ── Lista de espera: gera o convite E envia por e-mail ─
        if ($acao === 'espera_convidar') {
            $espera = new RepositorioListaEspera();
            $id = (int)$requisicao->post('espera_id', '0');
            $lead = $espera->buscar($id);
            if ($lead === null) {
                Sessao::flash('erro', 'Interessado não encontrado.');
                Resposta::redirecionarRota('admin.painel');
            }
            $codigo = (new RepositorioConvitesFamilia())->criar(
                'fundador', Autenticacao::id(), 7, 'Lista de espera: ' . $lead['email']
            );
            $link = url_absoluta('comecar', ['codigo' => $codigo]);
            $enviado = (new \App\Services\ServicoEmail())->enviarHtml(
                (string)$lead['email'],
                'Seu convite chegou — Diário do Bebê',
                self::htmlEmailConvite((string)$lead['nome'], $link),
                "Olá, {$lead['nome']}!\n\nSeu convite de família fundadora do Diário do Bebê chegou: "
                . "acesso completo e gratuito.\n\nCrie a sua família (leva 1 minuto): {$link}\n\n"
                . "O link vale por 7 dias e cria uma única família.\n"
                . "Guia rápido de uso: " . url_absoluta('ajuda'),
                'convite_familia',
                $id
            );
            $espera->mudarStatus($id, 'convidado');
            Sessao::definir('_link_convite_plataforma', $link);
            Sessao::flash(
                $enviado ? 'sucesso' : 'aviso',
                $enviado
                    ? 'Convite enviado por e-mail para ' . $lead['email'] . ' (o link também está abaixo, se quiser mandar no WhatsApp).'
                    : 'O e-mail falhou (veja o relatório de e-mails) — envie o link abaixo manualmente.'
            );
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'espera_descartar') {
            (new RepositorioListaEspera())->mudarStatus((int)$requisicao->post('espera_id', '0'), 'descartado');
            Sessao::flash('sucesso', 'Interessado marcado como descartado.');
            Resposta::redirecionarRota('admin.painel');
        }

        // ── Novidades (comunicados de novas funcionalidades) ──
        if ($acao === 'novidade_criar') {
            $titulo = trim((string)$requisicao->post('titulo', ''));
            $resumo = trim((string)$requisicao->post('resumo', ''));
            $detalhes = trim((string)$requisicao->post('detalhes', ''));
            if ($titulo === '' || $resumo === '' || $detalhes === '') {
                Sessao::flash('erro', 'Preencha título, resumo e detalhes da novidade.');
            } else {
                (new \App\Repositories\RepositorioNovidades())->criar(
                    mb_substr($titulo, 0, 120), mb_substr($resumo, 0, 300), $detalhes, Autenticacao::id()
                );
                Sessao::flash('sucesso', 'Novidade publicada em /novidades. Agora você pode enviá-la por e-mail.');
            }
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'novidade_enviar') {
            $novidades = new \App\Repositories\RepositorioNovidades();
            $novidade = $novidades->buscar((int)$requisicao->post('novidade_id', '0'));
            if ($novidade === null) {
                Sessao::flash('erro', 'Novidade não encontrada.');
                Resposta::redirecionarRota('admin.painel');
            }
            $email = new \App\Services\ServicoEmail();
            $linkDetalhes = url_absoluta('novidades') . '#' . $novidade['slug'];
            $enviados = 0;
            $falhas = 0;
            foreach ($novidades->destinatarios() as $destinatario) {
                $nomeCurto = e(mb_convert_case(trim(explode(' ', (string)$destinatario['nome'])[0]), MB_CASE_TITLE, 'UTF-8'));
                $ok = $email->enviarHtml(
                    (string)$destinatario['email'],
                    'Novidade no Diário do Bebê: ' . $novidade['titulo'],
                    '<p style="margin:0 0 6px; font-size:13px; font-weight:700; color:#3E6A64; text-transform:uppercase; letter-spacing:1px;">Novidade no app 🎉</p>'
                    . '<h1 style="margin:0 0 12px; font-size:22px; line-height:1.25;">' . e((string)$novidade['titulo']) . '</h1>'
                    . '<p style="margin:0 0 12px;">Olá, ' . $nomeCurto . '! ' . e((string)$novidade['resumo']) . '</p>'
                    . \App\Services\ServicoEmail::botao('Ver como funciona', $linkDetalhes)
                    . '<p style="margin:10px 0 0; font-size:13px; color:#A8A296;">A novidade já está disponível no seu app — nada a instalar.</p>',
                    "Olá, {$destinatario['nome']}!\n\nNovidade no Diário do Bebê: {$novidade['titulo']}\n\n"
                    . "{$novidade['resumo']}\n\nVeja como funciona: {$linkDetalhes}",
                    'novidade',
                    (int)$novidade['id'],
                    (int)$destinatario['familia_id']
                );
                $ok ? $enviados++ : $falhas++;
            }
            $novidades->marcarEmailEnviado((int)$novidade['id']);
            Sessao::flash(
                $falhas === 0 ? 'sucesso' : 'aviso',
                "Novidade enviada: {$enviados} e-mail(s)" . ($falhas > 0 ? ", {$falhas} falha(s) — veja o relatório" : '') . '.'
            );
            Resposta::redirecionarRota('admin.painel');
        }
        if ($acao === 'novidade_publicar') {
            (new \App\Repositories\RepositorioNovidades())->alternarPublicado((int)$requisicao->post('novidade_id', '0'));
            Sessao::flash('sucesso', 'Visibilidade da novidade alternada.');
            Resposta::redirecionarRota('admin.painel');
        }

        $familia = $familias->buscarPorCodigo((string)$requisicao->post('familia', ''));
        if ($familia === null || $familia['plano'] === 'plataforma') {
            Sessao::flash('erro', 'Família não encontrada.');
            Resposta::redirecionarRota('admin.painel');
        }

        switch ($acao) {
            case 'suspender':
                $familias->alterarStatus((int)$familia['id'], 'suspensa');
                Sessao::flash('sucesso', 'Família suspensa (o login dos usuários dela fica bloqueado).');
                break;
            case 'reativar':
                $familias->alterarStatus((int)$familia['id'], 'ativa');
                Sessao::flash('sucesso', 'Família reativada.');
                break;
            case 'plano':
                $familias->alterarPlano((int)$familia['id'], $planoValido((string)$requisicao->post('novo_plano', 'fundador')));
                Sessao::flash('sucesso', 'Plano atualizado.');
                break;
            case 'excluir':
                // Exclusão definitiva exige digitar o nome exato da família
                if ((string)$requisicao->post('confirmacao', '') !== (string)$familia['nome']) {
                    Sessao::flash('erro', 'Digite o nome exato da família para confirmar a exclusão definitiva.');
                    break;
                }
                $servico->excluirFamilia((int)$familia['id']);
                Sessao::flash('sucesso', 'Família e todos os seus dados foram excluídos definitivamente.');
                break;
            default:
                Sessao::flash('erro', 'Ação desconhecida.');
        }
        $log->registrar(null, Autenticacao::id(), 'plataforma_' . $acao, 'familias', (int)$familia['id'], $requisicao->ip());
        Resposta::redirecionarRota('admin.painel');
    }

    /** Corpo HTML do e-mail de convite de família fundadora. */
    private static function htmlEmailConvite(string $nome, string $link): string
    {
        $nomeSeguro = e(mb_convert_case(trim(explode(' ', $nome)[0]), MB_CASE_TITLE, 'UTF-8'));
        return '<p style="margin:0 0 6px; font-size:13px; font-weight:700; color:#B05E3C; '
            . 'text-transform:uppercase; letter-spacing:1px;">Convite de família fundadora</p>'
            . '<h1 style="margin:0 0 12px; font-size:22px; line-height:1.25;">Olá, ' . $nomeSeguro . '! Seu convite chegou 💚</h1>'
            . '<p style="margin:0 0 12px;">Vocês agora fazem parte das <strong>famílias fundadoras</strong> do '
            . 'Diário do Bebê — <strong>acesso completo e gratuito</strong> ao diário digital da rotina do bebê: '
            . 'a babá registra o dia em 2 toques, vocês acompanham em tempo real, e a ficha com QR code vai junto '
            . 'na consulta do pediatra.</p>'
            . \App\Services\ServicoEmail::botao('Criar a nossa família', $link)
            . '<p style="margin:16px 0 6px; font-weight:700;">Como começar (3 passos):</p>'
            . '<ol style="margin:0 0 12px; padding-left:20px; color:#7D776C;">'
            . '<li style="margin:4px 0;">Toque no botão e crie a família (leva 1 minuto).</li>'
            . '<li style="margin:4px 0;">Cadastre o bebê e adicione à tela inicial do celular.</li>'
            . '<li style="margin:4px 0;">Convide quem cuida: o outro responsável, a babá, os avós.</li></ol>'
            . '<p style="margin:0 0 4px;"><a href="' . e(url_absoluta('ajuda')) . '" style="color:#3E6A64; font-weight:700;">'
            . 'Guia rápido de uso (2 min) →</a></p>'
            . '<p style="margin:10px 0 0; font-size:13px; color:#A8A296;">O link vale por 7 dias e cria uma única família. '
            . 'Dúvidas? É só responder esta mensagem.</p>';
    }
}
