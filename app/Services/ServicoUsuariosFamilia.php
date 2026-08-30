<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Core\Identificadores;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioUsuarios;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Gestão de usuários da família e convites (v1: sem cadastro público).
 */
final class ServicoUsuariosFamilia
{
    private const PAPEIS_CONVIDAVEIS = ['admin_familia', 'responsavel', 'cuidador', 'leitor'];

    public function __construct(
        private readonly RepositorioUsuariosFamilia $usuarios = new RepositorioUsuariosFamilia(),
        private readonly RepositorioUsuarios $usuariosSistema = new RepositorioUsuarios(),
        private readonly ServicoEmail $email = new ServicoEmail(),
        private readonly RepositorioLogAcessos $log = new RepositorioLogAcessos(),
    ) {
    }

    /**
     * Cria um convite e envia por e-mail.
     * @return array{erro:?string, link:?string}
     */
    public function convidar(string $email, string $papel, string $ip): array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['erro' => 'Informe um e-mail válido.', 'link' => null];
        }
        if (!in_array($papel, self::PAPEIS_CONVIDAVEIS, true)) {
            return ['erro' => 'Papel inválido.', 'link' => null];
        }
        if ($this->usuariosSistema->buscarPorEmail($email) !== null) {
            return ['erro' => 'Já existe uma conta com este e-mail.', 'link' => null];
        }
        if ($this->usuarios->temConvitePendente($email)) {
            return ['erro' => 'Já há um convite pendente para este e-mail (cancele-o para reenviar).', 'link' => null];
        }

        $token = Identificadores::token();
        $conviteId = $this->usuarios->criarConvite(
            $email,
            $papel,
            Identificadores::hashToken($token),
            Autenticacao::id()
        );
        $link = url_absoluta('convite.aceitar', ['token' => $token]);

        $this->email->enviar(
            $email,
            'Convite — Diário do Bebê',
            "Olá!\n\n"
            . Autenticacao::usuario()['nome'] . " convidou você para acompanhar o Diário do Bebê da família.\n"
            . "Para criar a sua conta, acesse o link abaixo (válido por 7 dias):\n\n"
            . $link . "\n\n"
            . "Se você não esperava este convite, ignore este e-mail.\n"
        );
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'convite_criado', 'convites', $conviteId, $ip);

        // O link também é devolvido para exibição — em hospedagem compartilhada
        // o e-mail pode atrasar/cair em spam; o admin pode copiá-lo e mandar por WhatsApp.
        return ['erro' => null, 'link' => $link];
    }

    public function cancelarConvite(int $conviteId, string $ip): ?string
    {
        $convite = $this->usuarios->buscarConvite($conviteId);
        if ($convite === null || $convite['aceito_em'] !== null) {
            return 'Convite inexistente ou já aceito.';
        }
        $this->usuarios->cancelarConvite($conviteId);
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'convite_cancelado', 'convites', $conviteId, $ip);
        return null;
    }

    /** Ativa/desativa um usuário da família. Retorna mensagem de erro ou null. */
    public function definirAtivo(string $codigoUsuario, bool $ativo, string $ip): ?string
    {
        $usuario = $this->usuarios->buscarPorCodigo($codigoUsuario);
        if ($usuario === null) {
            return 'Usuário não encontrado.';
        }
        if ((int)$usuario['id'] === Autenticacao::id()) {
            return 'Você não pode desativar a própria conta.';
        }
        if (!$ativo && $usuario['papel'] === 'admin_familia' && $this->contarAdminsAtivos() <= 1) {
            return 'A família precisa de pelo menos um administrador ativo.';
        }
        $this->usuarios->definirAtivo((int)$usuario['id'], $ativo);
        $this->log->registrar(
            Autenticacao::familiaId(),
            Autenticacao::id(),
            $ativo ? 'usuario_ativado' : 'usuario_desativado',
            'usuarios',
            (int)$usuario['id'],
            $ip
        );
        return null;
    }

    public function alterarPapel(string $codigoUsuario, string $papel, string $ip): ?string
    {
        if (!in_array($papel, self::PAPEIS_CONVIDAVEIS, true)) {
            return 'Papel inválido.';
        }
        $usuario = $this->usuarios->buscarPorCodigo($codigoUsuario);
        if ($usuario === null) {
            return 'Usuário não encontrado.';
        }
        if ((int)$usuario['id'] === Autenticacao::id()) {
            return 'Altere o próprio papel pedindo a outro administrador.';
        }
        if ($usuario['papel'] === 'admin_familia' && $papel !== 'admin_familia'
            && $this->contarAdminsAtivos() <= 1) {
            return 'A família precisa de pelo menos um administrador.';
        }
        $this->usuarios->alterarPapel((int)$usuario['id'], $papel);
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'usuario_papel_alterado', 'usuarios', (int)$usuario['id'], $ip);
        return null;
    }

    private function contarAdminsAtivos(): int
    {
        $total = 0;
        foreach ($this->usuarios->listar() as $usuario) {
            if ($usuario['papel'] === 'admin_familia' && (int)$usuario['ativo'] === 1) {
                $total++;
            }
        }
        return $total;
    }
}
