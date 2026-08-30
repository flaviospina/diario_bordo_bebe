<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Middleware\ExigeConsentimentoLgpd;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioConsentimentos;
use App\Repositories\RepositorioLogAcessos;

/**
 * Termos de consentimento LGPD. Cada papel tem termo próprio — a babá também
 * é titular de dados (o termo dela explica o que é registrado sobre ela).
 * Sem aceite da versão vigente, o middleware não deixa navegar.
 */
final class LgpdController
{
    private const TIPOS = ['responsavel', 'cuidador', 'leitor'];

    public function termo(Requisicao $requisicao): void
    {
        $tipo = $requisicao->parametro('tipo');
        if (!in_array($tipo, self::TIPOS, true)) {
            Visao::erro404();
        }
        // Cada usuário só aceita o termo do próprio papel
        $tipoDoUsuario = ExigeConsentimentoLgpd::tipoTermoParaPapel(Autenticacao::papel());
        if ($tipo !== $tipoDoUsuario) {
            Resposta::redirecionarRota('lgpd.termo', ['tipo' => $tipoDoUsuario]);
        }

        $jaAceitou = (new RepositorioConsentimentos())
            ->aceitouVersaoVigente(Autenticacao::id(), $tipo);

        Visao::exibir('lgpd/termo', [
            'titulo'    => 'Termo de consentimento',
            'tipo'      => $tipo,
            'versao'    => LGPD_VERSOES_TERMOS[$tipo],
            'jaAceitou' => $jaAceitou,
        ]);
    }

    public function aceitar(Requisicao $requisicao): void
    {
        $tipo = $requisicao->parametro('tipo');
        $tipoDoUsuario = ExigeConsentimentoLgpd::tipoTermoParaPapel(Autenticacao::papel());
        if ($tipo !== $tipoDoUsuario || $requisicao->post('aceito') !== 'sim') {
            Sessao::flash('erro', 'É preciso marcar o aceite para continuar.');
            Resposta::redirecionarRota('lgpd.termo', ['tipo' => $tipoDoUsuario]);
        }

        $consentimentos = new RepositorioConsentimentos();
        if (!$consentimentos->aceitouVersaoVigente(Autenticacao::id(), $tipo)) {
            $consentimentos->registrarAceite(Autenticacao::id(), $tipo, $requisicao->ip());
            (new RepositorioLogAcessos())->registrar(
                Autenticacao::familiaId(),
                Autenticacao::id(),
                'lgpd_aceite',
                'consentimentos_lgpd',
                null,
                $requisicao->ip(),
                $requisicao->userAgent()
            );
        }
        Autenticacao::marcarConsentimentoOk();
        Resposta::redirecionarRota('home');
    }
}
