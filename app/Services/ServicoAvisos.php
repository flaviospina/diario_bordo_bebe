<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioAvisos;
use App\Repositories\RepositorioSistema;

/**
 * Avisos dentro do app (sino no topo) + push nos aparelhos inscritos.
 *
 * Quem recebe o quê:
 *  - cuidadores: ações dos pais que mexem no trabalho deles — registro
 *    ajustado/excluído, solicitação decidida, roteiro alterado, suprimento
 *    resolvido, ciência de intercorrência, registro de saúde feito pelos
 *    pais, configurações da família alteradas;
 *  - responsáveis: os mesmos eventos que já vão para a fila de e-mail/
 *    WhatsApp (intercorrência, solicitação, suprimento acabou, medição
 *    pendente, ficha aberta pelo pediatra).
 *
 * Nunca lança exceção para quem chamou: avisar é efeito colateral.
 */
final class ServicoAvisos extends RepositorioSistema
{
    public function __construct(private readonly ?int $familiaId = null)
    {
        parent::__construct();
    }

    /** @param int[] $usuarioIds */
    public function avisar(array $usuarioIds, string $tipo, string $titulo, ?string $mensagem, ?string $url): void
    {
        $usuarioIds = array_values(array_unique(array_filter(array_map('intval', $usuarioIds))));
        if ($usuarioIds === []) {
            return;
        }
        try {
            $avisos = new RepositorioAvisos($this->familiaId);
            foreach ($usuarioIds as $usuarioId) {
                $avisos->criar($usuarioId, $tipo, $titulo, $mensagem, $url);
            }
            (new ServicoPush())->enviarParaUsuarios($usuarioIds, $titulo, $mensagem ?? '', $url, $tipo);
        } catch (\Throwable $excecao) {
            error_log('Falha ao criar aviso: ' . $excecao->getMessage());
        }
    }

    /** Avisa todos os cuidadores ativos da família (exceto quem causou a ação). */
    public function avisarCuidadores(string $tipo, string $titulo, ?string $mensagem, ?string $url): void
    {
        $this->avisar($this->idsPorPapel(['cuidador']), $tipo, $titulo, $mensagem, $url);
    }

    /** Avisa os responsáveis ativos (exceto quem causou a ação). */
    public function avisarResponsaveis(string $tipo, string $titulo, ?string $mensagem, ?string $url): void
    {
        $this->avisar($this->idsPorPapel(['responsavel', 'admin_familia']), $tipo, $titulo, $mensagem, $url);
    }

    /** Avisa um usuário específico (ex.: o autor do registro), nunca o próprio autor da ação. */
    public function avisarUsuario(int $usuarioId, string $tipo, string $titulo, ?string $mensagem, ?string $url): void
    {
        if ($usuarioId <= 0 || $usuarioId === $this->autorId()) {
            return;
        }
        $this->avisar([$usuarioId], $tipo, $titulo, $mensagem, $url);
    }

    /** Primeiro nome de quem está agindo (para "Flávio ajustou…"). */
    public static function nomeDoAutor(): string
    {
        $nome = (string)(Autenticacao::usuario()['nome'] ?? 'Um responsável');
        return trim(explode(' ', $nome)[0]) ?: 'Um responsável';
    }

    /** @param string[] $papeis @return int[] */
    private function idsPorPapel(array $papeis): array
    {
        $familiaId = $this->familiaId ?? Autenticacao::familiaId();
        if ($familiaId <= 0) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($papeis), '?'));
        $declaracao = $this->bd->prepare(
            "SELECT id FROM usuarios WHERE familia_id = ? AND ativo = 1 AND papel IN ({$marcadores})"
        );
        $declaracao->execute([$familiaId, ...$papeis]);
        $ids = array_map('intval', $declaracao->fetchAll(\PDO::FETCH_COLUMN));
        $autor = $this->autorId();
        return array_values(array_filter($ids, static fn(int $id): bool => $id !== $autor));
    }

    private function autorId(): int
    {
        try {
            return Autenticacao::estaLogado() ? Autenticacao::id() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
