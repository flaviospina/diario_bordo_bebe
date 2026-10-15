<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Sessao;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioRoteiro;

/**
 * Monta a grade do dia (a mesma estrutura serve à tela do cuidador, à dos
 * pais e ao endpoint /api/dia usado no polling).
 *
 * Estados por linha:
 *   cinza    — bloco/slot futuro
 *   azul     — em andamento (agora dentro do intervalo, sem registro ainda)
 *   verde    — feito
 *   ambar    — atrasado (passou até a tolerância sem registro) ou parcial
 *   vermelho — passou sem registro além da tolerância, ou marcado "não feito"
 */
final class ServicoGrade
{
    public function __construct(
        private readonly ServicoConfiguracoes $config = new ServicoConfiguracoes(),
        private readonly RepositorioRegistros $registros = new RepositorioRegistros(),
        private readonly RepositorioRoteiro $roteiro = new RepositorioRoteiro(),
    ) {
    }

    /** Criança selecionada na sessão (ou a primeira ativa). */
    public function criancaAtual(?string $slugPedido = null): ?array
    {
        $repositorio = new RepositorioCriancas();
        if ($slugPedido !== null && $slugPedido !== '') {
            $crianca = $repositorio->buscarPorSlug($slugPedido);
            if ($crianca !== null) {
                Sessao::definir('_crianca_slug', $crianca['slug']);
                return $crianca;
            }
        }
        $slugSessao = Sessao::obter('_crianca_slug');
        if (is_string($slugSessao)) {
            $crianca = $repositorio->buscarPorSlug($slugSessao);
            if ($crianca !== null) {
                return $crianca;
            }
        }
        $ativas = $repositorio->listar();
        if ($ativas !== []) {
            Sessao::definir('_crianca_slug', $ativas[0]['slug']);
            return $ativas[0];
        }
        return null;
    }

    /**
     * @param bool $diaInteiro true para os responsáveis: a grade cobre as 24 h
     *                         (registram de dia e de noite), não só a janela
     *                         de trabalho do cuidador.
     * @return array{
     *   data:string, janela:array, modo:string, linhas:array, avulsos:array,
     *   estatisticas:array, ultima_atividade:?string, versao:string
     * }
     */
    public function montarDia(array $crianca, string $data, bool $diaInteiro = false): array
    {
        $agora = time();
        $janela = $diaInteiro
            ? ['inicio' => '00:00', 'fim' => '23:59', 'dia_inteiro' => true]
            : $this->config->janelaParaData($data);
        $granularidade = $this->config->obter('granularidade');
        $roteiroAtivo = (bool)$this->config->obter('roteiro_ativo');
        $tolerancia = (int)$this->config->obter('tolerancia_atraso_minutos') * 60;

        $registros = $this->registros->listarDoDia((int)$crianca['id'], $data);
        $linhas = [];
        $avulsos = $registros;

        if ($roteiroAtivo) {
            $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'];
            $diaSemana = $dias[(int)(new \DateTime($data))->format('w')];
            $blocos = $this->roteiro->listarParaDia((int)$crianca['id'], $diaSemana);
            $usados = [];

            foreach ($blocos as $bloco) {
                $inicioTs = strtotime($data . ' ' . $bloco['hora_inicio']);
                $fimTs = strtotime($data . ' ' . $bloco['hora_fim']);
                $doBloco = [];
                foreach ($registros as $indice => $registro) {
                    if ((int)($registro['roteiro_bloco_id'] ?? 0) === (int)$bloco['id']) {
                        $doBloco[] = $registro;
                        $usados[$indice] = true;
                    }
                }
                $linhas[] = [
                    'tipo'      => 'bloco',
                    'bloco'     => $bloco,
                    'registros' => $doBloco,
                    'estado'    => $this->estadoDaLinha($doBloco, $inicioTs, $fimTs, $agora, $tolerancia, (bool)$bloco['obrigatorio']),
                ];
            }
            $avulsos = array_values(array_diff_key($registros, $usados));
        } elseif ($granularidade !== 'flexivel') {
            $duracao = (int)$granularidade * 60;
            $inicioTs = strtotime($data . ' ' . $janela['inicio']);
            $fimJanelaTs = $diaInteiro ? strtotime($data . ' 23:59:59') + 1 : strtotime($data . ' ' . $janela['fim']);
            $dentroDeSlot = [];
            for ($ts = $inicioTs; $ts < $fimJanelaTs; $ts += $duracao) {
                $fimTs = min($ts + $duracao, $fimJanelaTs);
                $doSlot = [];
                foreach ($registros as $indice => $registro) {
                    $inicioRegistro = strtotime((string)$registro['inicio']);
                    if ($inicioRegistro >= $ts && $inicioRegistro < $fimTs) {
                        $doSlot[] = $registro;
                        $dentroDeSlot[$indice] = true;
                    }
                }
                $linhas[] = [
                    'tipo'      => 'slot',
                    'inicio'    => date('H:i', $ts),
                    'fim'       => date('H:i', $fimTs),
                    'registros' => $doSlot,
                    // Slot sem prescrição não fica vermelho: vazio no passado é apenas neutro
                    'estado'    => $this->estadoDaLinha($doSlot, $ts, $fimTs, $agora, $tolerancia, false),
                ];
            }
            // Registros fora da janela (madrugada, noite) nunca somem: viram avulsos
            $avulsos = array_values(array_diff_key($registros, $dentroDeSlot));
        }

        $estatisticas = $this->registros->estatisticasDoDia((int)$crianca['id'], $data);
        $ultima = null;
        foreach ($registros as $registro) {
            if ($ultima === null || $registro['criado_em'] > $ultima) {
                $ultima = (string)$registro['criado_em'];
            }
        }

        return [
            'data' => $data,
            'janela' => $janela,
            'modo' => $roteiroAtivo ? 'roteiro' : ($granularidade === 'flexivel' ? 'flexivel' : 'slots'),
            'linhas' => $linhas,
            'avulsos' => $avulsos,
            'estatisticas' => $estatisticas,
            'ultima_atividade' => $ultima,
            // hash usado pelo polling dos pais para saber se algo mudou
            'versao' => md5(json_encode([$registros, $ultima]) ?: ''),
        ];
    }

    private function estadoDaLinha(array $registros, int $inicioTs, int $fimTs, int $agora, int $tolerancia, bool $obrigatorio): string
    {
        foreach ($registros as $registro) {
            if ($registro['status'] === 'nao_feito') {
                return 'vermelho';
            }
            if ($registro['status'] === 'parcial') {
                return 'ambar';
            }
        }
        if ($registros !== []) {
            return 'verde';
        }
        if ($agora < $inicioTs) {
            return 'cinza';
        }
        if ($agora <= $fimTs) {
            return 'azul';
        }
        // Passou sem registro
        if (!$obrigatorio) {
            return 'cinza'; // linha não prescrita: vazio no passado é neutro
        }
        return $agora <= $fimTs + $tolerancia ? 'ambar' : 'vermelho';
    }
}
