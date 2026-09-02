<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioConvitesFamilia;
use App\Services\ServicoAutenticacao;
use App\Services\ServicoCadastroFamilia;

/**
 * Auto-cadastro de família fundadora: o casal abre o link/convite que o
 * responsável pela plataforma enviou e cria a própria família.
 */
final class ComecarController
{
    public function mostrar(Requisicao $requisicao): void
    {
        $codigo = $requisicao->parametro('codigo');
        $convite = (new RepositorioConvitesFamilia())->buscarValido($codigo);
        if ($convite === null) {
            Visao::exibir('autenticacao/comecar_invalido', [
                'titulo' => 'Convite indisponível',
            ], 'autenticacao', 410);
        }
        Visao::exibir('autenticacao/comecar', [
            'titulo' => 'Criar a sua família',
            'codigo' => $codigo,
            'convite' => $convite,
            // Prévia do link no WhatsApp: arte própria de convite
            'ogTitulo' => 'Vocês foram convidados — Diário do Bebê',
            'ogDescricao' => 'Convite de família fundadora: acesso completo e gratuito ao diário digital da rotina do bebê. Leva 1 minuto para começar.',
            'ogImagem' => 'img/og/convite.png',
        ], 'autenticacao');
    }

    public function criar(Requisicao $requisicao): void
    {
        $codigo = $requisicao->parametro('codigo');
        $senha = (string)$requisicao->post('senha', '');
        $resultado = (new ServicoCadastroFamilia())->criarPorConvite($codigo, [
            'familia' => (string)$requisicao->post('familia', ''),
            'nome' => (string)$requisicao->post('nome', ''),
            'email' => (string)$requisicao->post('email', ''),
            'senha' => $senha,
            'telefone' => $requisicao->post('telefone'),
            'aceite' => $requisicao->post('aceite') === 'sim',
        ], $requisicao->ip());

        if ($resultado['erro'] !== null) {
            Sessao::flash('erro', $resultado['erro']);
            Resposta::redirecionarRota('comecar', ['codigo' => $codigo]);
        }

        // Entra direto com a conta recém-criada e cai no primeiro passo
        $login = (new ServicoAutenticacao())->entrar(
            (string)$resultado['email'], $senha, $requisicao->ip(), $requisicao->userAgent()
        );
        if (!$login['ok']) {
            Sessao::flash('sucesso', 'Família criada! Entre com seu e-mail e a senha escolhida.');
            Resposta::redirecionarRota('login');
        }
        Sessao::flash('sucesso', 'Bem-vindos ao Diário do Bebê! Primeiro passo: cadastre o seu bebê.');
        Resposta::redirecionarRota('config.criancas');
    }
}
