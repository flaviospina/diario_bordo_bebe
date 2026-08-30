<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioResumos;

/**
 * Resumo diário em LINGUAGEM NATURAL (regra 8.7) — um texto corrido,
 * não uma lista crua de eventos. Gerado por templates em pt-BR.
 */
final class ServicoResumo
{
    public function __construct(private readonly int $familiaId)
    {
    }

    /** Gera (se ainda não existir) e enfileira o resumo de cada criança ativa. */
    public function gerarEEnviarDoDia(string $data): int
    {
        $criancas = (new RepositorioCriancas($this->familiaId))->listar();
        $resumos = new RepositorioResumos($this->familiaId);
        $config = new ServicoConfiguracoes(new \App\Repositories\RepositorioConfiguracoes($this->familiaId));
        $resumoConfig = $config->obter('resumo_diario');
        $canais = array_values(array_intersect((array)($resumoConfig['canais'] ?? ['email']), ['whatsapp', 'email']));
        $gerados = 0;

        foreach ($criancas as $crianca) {
            if ($resumos->existeParaDia((int)$crianca['id'], $data)) {
                continue;
            }
            $texto = $this->gerarTexto($crianca, $data);
            $resumos->criar((int)$crianca['id'], $data, $texto, $canais);
            (new ServicoNotificacoes())->enfileirarParaResponsaveis(
                $this->familiaId,
                'resumo_diario',
                'Resumo do dia de ' . ($crianca['apelido'] ?: $crianca['nome']),
                $texto,
                ['crianca' => $crianca['slug'], 'data' => $data],
                $canais
            );
            $gerados++;
        }
        return $gerados;
    }

    public function gerarTexto(array $crianca, string $data): string
    {
        $registros = (new RepositorioRegistros($this->familiaId))->listarDoDia((int)$crianca['id'], $data);
        $nome = (string)($crianca['apelido'] ?: $crianca['nome']);
        $dataBr = data_br($data . ' 00:00:00', 'd/m/Y');

        if ($registros === []) {
            return "Resumo de {$nome} — {$dataBr}: nenhum registro foi feito neste dia.";
        }

        // ── Agregações ────────────────────────────────────────
        $mamadas = 0;
        $volumeMl = 0;
        $sonecas = 0;
        $minutosSono = 0;
        $fraldas = 0;
        $fraldasComCoco = 0;
        $refeicoes = [];
        $medicacoes = [];
        $marcos = [];
        $banho = false;

        foreach ($registros as $registro) {
            $dados = json_decode((string)($registro['dados'] ?? 'null'), true) ?: [];
            switch ($registro['categoria_slug']) {
                case 'amamentacao':
                    $mamadas++;
                    break;
                case 'mamadeira':
                    $mamadas++;
                    $volumeMl += (int)($dados['volume_ml'] ?? 0) - (int)($dados['volume_restante_ml'] ?? 0);
                    break;
                case 'papinha':
                    $refeicoes[] = (string)($dados['alimento'] ?? 'papinha');
                    break;
                case 'soneca':
                case 'sono-noturno':
                    $sonecas++;
                    if ($registro['fim'] !== null) {
                        $minutosSono += max(0, (int)((strtotime((string)$registro['fim']) - strtotime((string)$registro['inicio'])) / 60));
                    }
                    break;
                case 'fralda':
                    $fraldas++;
                    if (in_array($dados['conteudo'] ?? '', ['coco', 'ambos'], true)) {
                        $fraldasComCoco++;
                    }
                    break;
                case 'banho':
                    $banho = true;
                    break;
                case 'medicacao':
                    $medicacoes[] = trim((string)($dados['nome'] ?? '') . ' ' . (string)($dados['dose'] ?? ''));
                    break;
                case 'marco-desenvolvimento':
                    $marcos[] = (string)($dados['descricao'] ?? '');
                    break;
            }
        }

        // ── Texto corrido ─────────────────────────────────────
        $frases = [];
        if ($mamadas > 0) {
            $frase = "{$nome} mamou {$mamadas} " . ($mamadas === 1 ? 'vez' : 'vezes');
            if ($volumeMl > 0) {
                $frase .= " (cerca de {$volumeMl} ml na mamadeira)";
            }
            $frases[] = $frase;
        }
        if ($refeicoes !== []) {
            $frases[] = 'comeu ' . $this->listarEmPortugues($refeicoes);
        }
        if ($sonecas > 0) {
            $frase = 'dormiu ' . $sonecas . ($sonecas === 1 ? ' vez' : ' vezes');
            if ($minutosSono > 0) {
                $horas = intdiv($minutosSono, 60);
                $minutos = $minutosSono % 60;
                $frase .= ', somando ' . ($horas > 0 ? "{$horas}h" : '') . ($minutos > 0 ? "{$minutos}min" : '');
            }
            $frases[] = $frase;
        }
        if ($fraldas > 0) {
            $frase = "teve {$fraldas} " . ($fraldas === 1 ? 'troca de fralda' : 'trocas de fralda');
            if ($fraldasComCoco > 0) {
                $frase .= " ({$fraldasComCoco} com cocô)";
            }
            $frases[] = $frase;
        }
        if ($banho) {
            $frases[] = 'tomou banho';
        }

        $texto = "Resumo de {$nome} — {$dataBr}.\n";
        if ($frases !== []) {
            $texto .= ucfirst($this->listarEmPortugues($frases, ' e ')) . '.';
        }
        if ($medicacoes !== []) {
            $texto .= "\nMedicações dadas: " . $this->listarEmPortugues($medicacoes) . '.';
        }

        $intercorrencias = array_values(array_filter(
            (new RepositorioIntercorrencias($this->familiaId))->listar(20),
            static fn(array $i): bool => substr((string)$i['ocorrido_em'], 0, 10) === $data
                && (int)$i['crianca_id'] === (int)$crianca['id']
        ));
        if ($intercorrencias !== []) {
            $texto .= "\nAtenção: houve " . count($intercorrencias)
                . (count($intercorrencias) === 1 ? ' intercorrência' : ' intercorrências')
                . ' — ' . $this->listarEmPortugues(array_map(
                    static fn(array $i): string => mb_strtolower((string)$i['gravidade']) . ': ' . (string)$i['descricao'],
                    $intercorrencias
                )) . '.';
        }
        if ($marcos !== []) {
            $texto .= "\n🌟 Marco do dia: " . $this->listarEmPortugues($marcos) . '.';
        }

        $ultimo = end($registros);
        $texto .= "\nÚltimo registro às " . data_br((string)$ultimo['inicio'], 'H:i')
            . ', num total de ' . count($registros) . ' registros no dia.';
        return $texto;
    }

    /** @param string[] $itens */
    private function listarEmPortugues(array $itens, string $conector = ' e '): string
    {
        $itens = array_values(array_filter(array_map('trim', $itens)));
        if (count($itens) <= 1) {
            return $itens[0] ?? '';
        }
        $ultimo = array_pop($itens);
        return implode(', ', $itens) . $conector . $ultimo;
    }
}
