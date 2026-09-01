<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Middleware\ExigeConsentimentoLgpd;
use App\Repositories\RepositorioConsentimentos;
use App\Repositories\RepositorioConvitesFamilia;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioSistema;
use App\Repositories\RepositorioUsuarios;

/**
 * Auto-cadastro de família por convite (/comecar/{codigo}) — fase fundadores.
 * O casal cria a PRÓPRIA família: nome, conta de admin e aceite LGPD numa
 * transação só; o convite queima no uso.
 */
final class ServicoCadastroFamilia extends RepositorioSistema
{
    /**
     * @param array{familia:string,nome:string,email:string,senha:string,telefone:?string,aceite:bool} $dados
     * @return array{erro:?string, email:?string}
     */
    public function criarPorConvite(string $codigo, array $dados, string $ip): array
    {
        $convites = new RepositorioConvitesFamilia();
        $convite = $convites->buscarValido($codigo);
        if ($convite === null) {
            return ['erro' => 'Convite inválido, expirado ou já utilizado.', 'email' => null];
        }

        $nomeFamilia = trim($dados['familia']);
        $nome = trim($dados['nome']);
        $email = mb_strtolower(trim($dados['email']));
        if ($nomeFamilia === '' || $nome === '') {
            return ['erro' => 'Informe o nome da família e o seu nome.', 'email' => null];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['erro' => 'Informe um e-mail válido.', 'email' => null];
        }
        $erroSenha = ServicoSenha::validarForcaSenha($dados['senha']);
        if ($erroSenha !== null) {
            return ['erro' => $erroSenha, 'email' => null];
        }
        if (!$dados['aceite']) {
            return ['erro' => 'É preciso aceitar o termo de uso e privacidade para continuar.', 'email' => null];
        }
        if ((new RepositorioUsuarios())->buscarPorEmail($email) !== null) {
            return ['erro' => 'Já existe uma conta com este e-mail. Entre pela tela de login.', 'email' => null];
        }

        $this->bd->beginTransaction();
        try {
            $familiaId = (new RepositorioFamilias())->criar($nomeFamilia, (string)$convite['plano']);
            $usuarioId = (new RepositorioUsuarios())->criar(
                $familiaId,
                $nome,
                $email,
                ServicoAutenticacao::gerarHashSenha($dados['senha']),
                'admin_familia',
                ($dados['telefone'] ?? '') !== '' ? $dados['telefone'] : null
            );
            (new RepositorioConsentimentos())->registrarAceite(
                $usuarioId,
                ExigeConsentimentoLgpd::tipoTermoParaPapel('admin_familia'),
                $ip
            );
            $convites->marcarUsado((int)$convite['id'], $familiaId);
            $this->bd->commit();
        } catch (\Throwable $excecao) {
            $this->bd->rollBack();
            throw $excecao;
        }

        (new RepositorioLogAcessos())->registrar(
            $familiaId, $usuarioId, 'familia_autocadastro', 'convites_familia', (int)$convite['id'], $ip
        );
        return ['erro' => null, 'email' => $email];
    }
}
