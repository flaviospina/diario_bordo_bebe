<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioListaEspera;

/**
 * Rota raiz: landing pública para visitantes; cada papel logado vai
 * para a sua tela principal.
 */
final class HomeController
{
    public function inicio(Requisicao $requisicao): void
    {
        if (!Autenticacao::estaLogado()) {
            Visao::exibir('publico/landing', ['titulo' => 'Diário do Bebê'], '');
        }

        match (Autenticacao::papel()) {
            'cuidador'    => Resposta::redirecionarRota('cuidador.dia'),
            'super_admin' => Resposta::redirecionarRota('admin.painel'),
            default       => Resposta::redirecionarRota('pais.acompanhar'), // responsavel, admin_familia, leitor
        };
    }

    /** Lista de espera da landing ("quero um convite"). */
    public function listaEspera(Requisicao $requisicao): void
    {
        $nome = trim((string)$requisicao->post('nome', ''));
        $email = mb_strtolower(trim((string)$requisicao->post('email', '')));
        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Sessao::flash('erro', 'Informe o seu nome e um e-mail válido.');
            Resposta::redirecionarRota('home');
        }
        (new RepositorioListaEspera())->inscrever(
            mb_substr($nome, 0, 120),
            mb_substr($email, 0, 190),
            trim((string)$requisicao->post('whatsapp', '')) ?: null,
            trim((string)$requisicao->post('mensagem', '')) ?: null,
            $requisicao->ip()
        );
        Sessao::flash('sucesso', 'Pedido recebido! Assim que abrir uma vaga de família fundadora, enviaremos o seu convite.');
        Resposta::redirecionarRota('home');
    }
}
