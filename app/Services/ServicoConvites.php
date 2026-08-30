<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Identificadores;
use App\Repositories\RepositorioConvites;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioUsuarios;

/**
 * Aceite de convites (a criação/gestão de convites chega na Fase 2;
 * o fluxo de entrada já existe desde a Fase 1 porque não há cadastro público).
 */
final class ServicoConvites
{
    public function __construct(
        private readonly RepositorioConvites $convites = new RepositorioConvites(),
        private readonly RepositorioUsuarios $usuarios = new RepositorioUsuarios(),
        private readonly RepositorioLogAcessos $log = new RepositorioLogAcessos(),
    ) {
    }

    public function buscarPorToken(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[0-9a-f]{64}$/', $token)) {
            return null;
        }
        return $this->convites->buscarValidoPorTokenHash(Identificadores::hashToken($token));
    }

    /**
     * Aceita o convite criando o usuário. Retorna mensagem de erro ou null.
     * @param array{nome:string,senha:string,telefone:?string} $dados
     */
    public function aceitar(string $token, array $dados, string $ip): ?string
    {
        $convite = $this->buscarPorToken($token);
        if ($convite === null) {
            return 'Convite inválido, expirado ou já utilizado.';
        }
        if (trim($dados['nome']) === '') {
            return 'Informe o seu nome.';
        }
        $erroSenha = ServicoSenha::validarForcaSenha($dados['senha']);
        if ($erroSenha !== null) {
            return $erroSenha;
        }
        if ($this->usuarios->buscarPorEmail((string)$convite['email']) !== null) {
            return 'Já existe uma conta com este e-mail. Entre pela tela de login.';
        }

        $usuarioId = $this->usuarios->criar(
            (int)$convite['familia_id'],
            trim($dados['nome']),
            (string)$convite['email'],
            ServicoAutenticacao::gerarHashSenha($dados['senha']),
            (string)$convite['papel'],
            $dados['telefone'] !== null && $dados['telefone'] !== '' ? $dados['telefone'] : null
        );
        $this->convites->marcarAceito((int)$convite['id'], $usuarioId);
        $this->log->registrar((int)$convite['familia_id'], $usuarioId, 'convite_aceito', 'convites', (int)$convite['id'], $ip);
        return null;
    }
}
