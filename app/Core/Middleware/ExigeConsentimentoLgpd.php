<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Repositories\RepositorioConsentimentos;

/**
 * "Sem aceite, sem acesso" (seção 10 do escopo): usuário autenticado que ainda
 * não aceitou a versão vigente do termo do seu papel é levado ao termo antes
 * de qualquer outra tela. A babá também é titular de dados — o termo do
 * cuidador é próprio, não o dos pais.
 */
final class ExigeConsentimentoLgpd
{
    public function tratar(Requisicao $requisicao): void
    {
        if (!Autenticacao::estaLogado() || Autenticacao::consentimentoVerificado()) {
            return;
        }
        // super_admin não acessa conteúdo de famílias; termo próprio fica fora da v1
        if (Autenticacao::papel() === 'super_admin') {
            Autenticacao::marcarConsentimentoOk();
            return;
        }

        $tipo = self::tipoTermoParaPapel(Autenticacao::papel());
        $repositorio = new RepositorioConsentimentos();
        if ($repositorio->aceitouVersaoVigente(Autenticacao::id(), $tipo)) {
            Autenticacao::marcarConsentimentoOk();
            return;
        }

        if ($requisicao->ehApi()) {
            Resposta::erroJson('Termo de consentimento pendente.', 403);
        }
        Resposta::redirecionarRota('lgpd.termo', ['tipo' => $tipo]);
    }

    public static function tipoTermoParaPapel(string $papel): string
    {
        return match ($papel) {
            'cuidador' => 'cuidador',
            'leitor'   => 'leitor',
            default    => 'responsavel', // admin_familia e responsavel
        };
    }
}
