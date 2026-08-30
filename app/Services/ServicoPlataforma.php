<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Core\Identificadores;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioSistema;
use App\Repositories\RepositorioUsuarios;

/**
 * Operações de plataforma (super_admin): onboarding de famílias, planos,
 * suspensão e exclusão definitiva (direito LGPD). O super_admin gerencia
 * tenants mas NUNCA lê conteúdo de registros — nada aqui retorna diário.
 */
final class ServicoPlataforma extends RepositorioSistema
{
    public function __construct(
        private readonly RepositorioFamilias $familias = new RepositorioFamilias(),
    ) {
        parent::__construct();
    }

    /**
     * Onboarding: cria a família e um convite de admin_familia.
     * @return array{erro:?string, link:?string}
     */
    public function criarFamiliaComConvite(string $nome, string $plano, string $emailAdmin): array
    {
        $nome = trim($nome);
        $emailAdmin = mb_strtolower(trim($emailAdmin));
        if ($nome === '' || !filter_var($emailAdmin, FILTER_VALIDATE_EMAIL)) {
            return ['erro' => 'Informe o nome da família e um e-mail válido.', 'link' => null];
        }
        if ((new RepositorioUsuarios())->buscarPorEmail($emailAdmin) !== null) {
            return ['erro' => 'Já existe uma conta com este e-mail.', 'link' => null];
        }

        $familiaId = $this->familias->criar($nome, in_array($plano, ['familiar', 'premium'], true) ? $plano : 'familiar');
        $token = Identificadores::token();
        $this->executar(
            'INSERT INTO convites (familia_id, email, papel, token_hash, convidado_por, expira_em)
             VALUES (:familia, :email, \'admin_familia\', :hash, :autor, DATE_ADD(NOW(), INTERVAL 14 DAY))',
            [
                'familia' => $familiaId,
                'email' => $emailAdmin,
                'hash' => Identificadores::hashToken($token),
                'autor' => Autenticacao::id(),
            ]
        );
        $link = url_absoluta('convite.aceitar', ['token' => $token]);
        (new ServicoEmail())->enviar(
            $emailAdmin,
            'Bem-vindo(a) ao Diário do Bebê',
            "Olá!\n\nSua família \"{$nome}\" foi criada no Diário do Bebê.\n"
            . "Crie a sua conta de administrador pelo link (válido por 14 dias):\n\n{$link}\n"
        );
        return ['erro' => null, 'link' => $link];
    }

    /**
     * Exclusão DEFINITIVA de uma família e de todos os seus dados
     * (direito do titular — LGPD). Irreversível; apaga arquivos também.
     */
    public function excluirFamilia(int $familiaId): void
    {
        $this->bd->beginTransaction();
        try {
            $usuarios = $this->executar('SELECT id FROM usuarios WHERE familia_id = :f', ['f' => $familiaId])
                ->fetchAll(\PDO::FETCH_COLUMN);

            // Arquivos físicos primeiro (fotos e exportações da família)
            foreach (['fotos', 'thumbs', 'exportacoes', 'pdfs'] as $pasta) {
                $caminho = STORAGE_PATH . '/' . $pasta . '/' . $familiaId;
                if (is_dir($caminho)) {
                    foreach (glob($caminho . '/*') ?: [] as $arquivo) {
                        @unlink($arquivo);
                    }
                    @rmdir($caminho);
                }
            }

            // Dependentes de registros
            $this->executar('DELETE rf FROM registro_fotos rf JOIN registros r ON r.id = rf.registro_id WHERE r.familia_id = :f', ['f' => $familiaId]);
            $this->executar('DELETE rv FROM registro_versoes rv JOIN registros r ON r.id = rv.registro_id WHERE r.familia_id = :f', ['f' => $familiaId]);
            $this->executar('DELETE FROM solicitacoes_edicao WHERE familia_id = :f', ['f' => $familiaId]);
            $this->executar('DELETE FROM registros WHERE familia_id = :f', ['f' => $familiaId]);

            foreach (['resumos_diarios', 'intercorrencias', 'turnos', 'suprimentos', 'fila_notificacoes',
                      'roteiro_blocos', 'categorias', 'arquivos_gerados', 'configuracoes_historico',
                      'configuracoes_familia', 'convites', 'criancas', 'log_acessos'] as $tabela) {
                $this->executar("DELETE FROM {$tabela} WHERE familia_id = :f", ['f' => $familiaId]);
            }

            if ($usuarios !== []) {
                $marcadores = implode(',', array_fill(0, count($usuarios), '?'));
                foreach (['consentimentos_lgpd', 'tokens_senha', 'perfis_responsaveis'] as $tabela) {
                    $this->bd->prepare("DELETE FROM {$tabela} WHERE usuario_id IN ({$marcadores})")->execute($usuarios);
                }
                $this->bd->prepare("DELETE FROM usuarios WHERE id IN ({$marcadores})")->execute($usuarios);
            }
            $this->executar('DELETE FROM familias WHERE id = :f', ['f' => $familiaId]);
            $this->bd->commit();
        } catch (\Throwable $excecao) {
            $this->bd->rollBack();
            throw $excecao;
        }
    }
}
