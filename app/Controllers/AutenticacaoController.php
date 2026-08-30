<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Services\ServicoAutenticacao;
use App\Services\ServicoSenha;

final class AutenticacaoController
{
    public function formularioEntrar(Requisicao $requisicao): void
    {
        if (Autenticacao::estaLogado()) {
            Resposta::redirecionarRota('home');
        }
        Visao::exibir('autenticacao/entrar', ['titulo' => 'Entrar'], 'autenticacao');
    }

    public function entrar(Requisicao $requisicao): void
    {
        $email = (string)$requisicao->post('email', '');
        $senha = (string)$requisicao->post('senha', '');

        if ($email === '' || $senha === '') {
            Sessao::flash('erro', 'Informe e-mail e senha.');
            Resposta::redirecionarRota('login');
        }

        $resultado = (new ServicoAutenticacao())->entrar($email, $senha, $requisicao->ip(), $requisicao->userAgent());
        if (!$resultado['ok']) {
            Sessao::flash('erro', $resultado['mensagem']);
            Resposta::redirecionarRota('login');
        }

        $destino = Sessao::obter('_destino_pos_login');
        Sessao::remover('_destino_pos_login');
        if (is_string($destino) && $destino !== '' && str_starts_with($destino, '/') && !str_starts_with($destino, '//')) {
            Resposta::redirecionarCaminho(BASE_PATH . $destino);
        }
        Resposta::redirecionarRota('home');
    }

    public function sair(Requisicao $requisicao): void
    {
        (new ServicoAutenticacao())->sair($requisicao->ip(), $requisicao->userAgent());
        Resposta::redirecionarRota('login');
    }

    public function formularioRecuperar(Requisicao $requisicao): void
    {
        Visao::exibir('autenticacao/senha_recuperar', ['titulo' => 'Recuperar senha'], 'autenticacao');
    }

    public function recuperar(Requisicao $requisicao): void
    {
        $email = (string)$requisicao->post('email', '');
        if ($email !== '') {
            (new ServicoSenha())->solicitarRecuperacao($email, $requisicao->ip());
        }
        // Resposta idêntica exista ou não a conta (não revelar cadastros)
        Sessao::flash('sucesso', 'Se este e-mail estiver cadastrado, você receberá um link para redefinir a senha.');
        Resposta::redirecionarRota('senha.recuperar');
    }

    public function formularioRedefinir(Requisicao $requisicao): void
    {
        $token = $requisicao->parametro('token');
        $servico = new ServicoSenha();
        if ($servico->validarToken($token) === null) {
            Sessao::flash('erro', 'Link inválido ou expirado. Peça uma nova recuperação de senha.');
            Resposta::redirecionarRota('senha.recuperar');
        }
        Visao::exibir('autenticacao/senha_redefinir', ['titulo' => 'Nova senha', 'token' => $token], 'autenticacao');
    }

    public function redefinir(Requisicao $requisicao): void
    {
        $token = $requisicao->parametro('token');
        $senha = (string)$requisicao->post('senha', '');
        $confirmacao = (string)$requisicao->post('senha_confirmacao', '');

        if ($senha !== $confirmacao) {
            Sessao::flash('erro', 'As senhas não conferem.');
            Resposta::redirecionarRota('senha.redefinir', ['token' => $token]);
        }

        $erro = (new ServicoSenha())->redefinir($token, $senha, $requisicao->ip());
        if ($erro !== null) {
            Sessao::flash('erro', $erro);
            Resposta::redirecionarRota('senha.recuperar');
        }
        Sessao::flash('sucesso', 'Senha redefinida. Entre com a nova senha.');
        Resposta::redirecionarRota('login');
    }
}
