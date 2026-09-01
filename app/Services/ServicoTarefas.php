<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\BancoDados;
use App\Repositories\RepositorioConfiguracoes;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioFilaNotificacoes;

/**
 * Tarefas agendadas (regra 8.5 e seção 9): acionadas pelo cron do cPanel
 * ou pelo scheduler do n8n via POST /api/tarefas/{tarefa} com token.
 * Rodam fora de sessão e percorrem todas as famílias ativas.
 */
final class ServicoTarefas
{
    public function __construct(
        private readonly RepositorioFamilias $familias = new RepositorioFamilias(),
    ) {
    }

    /** Despacha a fila de notificações e fecha turnos esquecidos. */
    public function processarFila(): array
    {
        $despacho = (new ServicoNotificacoes())->dispararPendentes();
        $turnosFechados = $this->fecharTurnosAntigos();
        $medicoesConfirmadas = (new ServicoConsulta())->confirmarAutomaticas();
        return $despacho + [
            'turnos_fechados' => $turnosFechados,
            'medicoes_confirmadas_automaticamente' => $medicoesConfirmadas,
        ];
    }

    /**
     * Alerta de omissão: famílias com o alerta ativo recebem aviso quando o
     * silêncio dentro da janela do dia ultrapassa o limite configurado.
     */
    public function verificarOmissao(): array
    {
        $alertadas = [];
        $fila = new RepositorioFilaNotificacoes();
        foreach ($this->familias->listarAtivas() as $familia) {
            $familiaId = (int)$familia['id'];
            $config = new ServicoConfiguracoes(new RepositorioConfiguracoes($familiaId));
            $alerta = $config->obter('alerta_omissao');
            if (empty($alerta['ativo'])) {
                continue;
            }
            $limiteMinutos = max(15, (int)($alerta['minutos'] ?? 90));

            $janela = $config->janelaParaData(hoje());
            $agora = date('H:i');
            if ($agora < $janela['inicio'] || $agora > $janela['fim']) {
                continue; // fora da janela do dia não há omissão
            }

            // Silêncio = tempo desde o último registro (ou desde o início da janela)
            $ultimo = (new \App\Repositories\RepositorioRegistros($familiaId))->ultimoRegistroDaFamilia();
            $referencia = max(
                strtotime(hoje() . ' ' . $janela['inicio']),
                $ultimo !== null ? strtotime($ultimo) : 0
            );
            $silencioMinutos = (int)((time() - $referencia) / 60);
            if ($silencioMinutos < $limiteMinutos) {
                continue;
            }
            // Anti-spam: no máximo um alerta por período de silêncio
            if ($fila->eventoRecente($familiaId, 'alerta_omissao', $limiteMinutos)) {
                continue;
            }

            (new ServicoNotificacoes())->enfileirarParaResponsaveis(
                $familiaId,
                'alerta_omissao',
                'Sem registros há ' . $silencioMinutos . ' min — Diário do Bebê',
                'Nenhuma atividade foi registrada no diário há ' . $silencioMinutos
                . ' minutos (limite configurado: ' . $limiteMinutos . ' min). '
                . 'Veja o dia em: ' . url_absoluta('pais.acompanhar'),
                ['silencio_minutos' => $silencioMinutos]
            );
            $alertadas[] = $familiaId;
        }
        (new ServicoNotificacoes())->dispararPendentes();
        return ['familias_alertadas' => $alertadas];
    }

    /** Resumo diário: gera e enfileira ao fim da janela (horário configurado). */
    public function gerarResumos(): array
    {
        $geradas = [];
        foreach ($this->familias->listarAtivas() as $familia) {
            $familiaId = (int)$familia['id'];
            $config = new ServicoConfiguracoes(new RepositorioConfiguracoes($familiaId));
            $resumo = $config->obter('resumo_diario');
            if (empty($resumo['ativo'])) {
                continue;
            }
            if (date('H:i') < (string)($resumo['horario'] ?? '19:30')) {
                continue; // ainda não chegou o horário de envio
            }
            $total = (new ServicoResumo($familiaId))->gerarEEnviarDoDia(hoje());
            if ($total > 0) {
                $geradas[$familiaId] = $total;
            }
        }
        (new ServicoNotificacoes())->dispararPendentes();
        return ['resumos_gerados' => $geradas];
    }

    /**
     * Expurgo LGPD: famílias com retenção configurada têm registros mais
     * antigos que N meses removidos de fato (com dependências e arquivos).
     */
    public function expurgarRetencao(): array
    {
        $bd = BancoDados::conexao();
        $expurgados = [];
        foreach ($this->familias->listarAtivas() as $familia) {
            $familiaId = (int)$familia['id'];
            $config = new ServicoConfiguracoes(new RepositorioConfiguracoes($familiaId));
            $meses = $config->obter('retencao_meses');
            if ($meses === null || (int)$meses < 6) {
                continue;
            }
            $limite = date('Y-m-d 00:00:00', strtotime('-' . (int)$meses . ' months'));

            $ids = $bd->prepare('SELECT id FROM registros WHERE familia_id = ? AND inicio < ?');
            $ids->execute([$familiaId, $limite]);
            $lista = $ids->fetchAll(\PDO::FETCH_COLUMN);
            if ($lista === []) {
                continue;
            }
            $marcadores = implode(',', array_fill(0, count($lista), '?'));

            // Apaga arquivos de foto do disco antes das linhas
            $fotos = $bd->prepare("SELECT caminho, thumb FROM registro_fotos WHERE registro_id IN ({$marcadores})");
            $fotos->execute($lista);
            foreach ($fotos->fetchAll() as $foto) {
                foreach ([$foto['caminho'], $foto['thumb']] as $relativo) {
                    if ($relativo !== null && is_file(STORAGE_PATH . '/' . $relativo)) {
                        @unlink(STORAGE_PATH . '/' . $relativo);
                    }
                }
            }
            foreach (['registro_fotos', 'registro_versoes'] as $tabela) {
                $bd->prepare("DELETE FROM {$tabela} WHERE registro_id IN ({$marcadores})")->execute($lista);
            }
            $bd->prepare("DELETE FROM solicitacoes_edicao WHERE registro_id IN ({$marcadores})")->execute($lista);
            $bd->prepare("DELETE FROM registros WHERE id IN ({$marcadores})")->execute($lista);
            $expurgados[$familiaId] = count($lista);
        }
        return ['registros_expurgados' => $expurgados];
    }

    /** Turnos sem saída de dias anteriores fecham no último registro do dia (regra 8.8). */
    private function fecharTurnosAntigos(): int
    {
        $bd = BancoDados::conexao();
        // Fecha no horário do último registro que o cuidador criou naquele dia;
        // sem registro posterior, fecha na própria entrada (turno de duração zero).
        $total = $bd->exec(
            "UPDATE turnos t
                SET t.saida = COALESCE(
                    (SELECT MAX(r.criado_em) FROM registros r
                      WHERE r.familia_id = t.familia_id AND r.usuario_id = t.usuario_id
                        AND DATE(r.criado_em) = DATE(t.entrada)),
                    t.entrada)
              WHERE t.saida IS NULL AND t.entrada < CURDATE()"
        );
        return (int)$total;
    }
}
