<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Percentis de crescimento pelos padrões da OMS (0–60 meses).
 *
 * O cálculo usa o método LMS oficial: z = ((valor/M)^L − 1) / (L·S), com
 * interpolação linear de L, M e S entre os meses. O resultado é CONGELADO na
 * linha de `medicoes` no momento da gravação — se a referência mudar no
 * futuro, relatórios já emitidos não mudam.
 *
 * Regra de produto: o sistema EXIBE o percentil e nunca o comenta.
 * Sem "abaixo do esperado", sem cor de alarme. Quem interpreta é o médico.
 */
final class ServicoCrescimento
{
    /** @var array<string,array<string,array<int,array{0:float,1:float,2:float}>>> */
    private array $tabelas;

    public function __construct()
    {
        $this->tabelas = require RAIZ_PROJETO . '/database/dados/oms_lms.php';
    }

    /** Idade em meses (fracionária) entre nascimento e a data da medição. */
    public static function idadeEmMeses(string $nascimento, string $dataMedicao): float
    {
        $dias = (strtotime($dataMedicao) - strtotime($nascimento)) / 86400;
        return $dias / 30.4375;
    }

    /** Idade humana: dias até 1 mês, meses até 2 anos, anos e meses depois. */
    public static function idadeFormatada(string $nascimento, ?string $referencia = null): string
    {
        $inicio = new \DateTime($nascimento);
        $fim = new \DateTime($referencia ?? 'now');
        if ($fim < $inicio) {
            return '—';
        }
        $diferenca = $inicio->diff($fim);
        $totalMeses = $diferenca->y * 12 + $diferenca->m;
        if ($totalMeses < 1) {
            return $diferenca->days . ' ' . ($diferenca->days === 1 ? 'dia' : 'dias');
        }
        if ($totalMeses < 24) {
            return $totalMeses . ' ' . ($totalMeses === 1 ? 'mês' : 'meses')
                . ($diferenca->d > 0 ? ' e ' . $diferenca->d . 'd' : '');
        }
        return $diferenca->y . ' ' . ($diferenca->y === 1 ? 'ano' : 'anos')
            . ($diferenca->m > 0 ? ' e ' . $diferenca->m . 'm' : '');
    }

    /**
     * Escore-z e percentil de um valor.
     * @param string $tipo  'peso' (kg) | 'altura' (cm) | 'pc' (cm)
     * @param string $sexo  'masculino' | 'feminino'
     * @return ?array{z:float, percentil:float} null quando fora da faixa 0–60m ou sem sexo
     */
    public function avaliar(string $tipo, string $sexo, float $idadeMeses, float $valor): ?array
    {
        $lms = $this->lmsInterpolado($tipo, $sexo, $idadeMeses);
        if ($lms === null || $valor <= 0) {
            return null;
        }
        [$l, $m, $s] = $lms;
        $z = abs($l) > 1e-6
            ? ((($valor / $m) ** $l) - 1) / ($l * $s)
            : log($valor / $m) / $s;
        return ['z' => round($z, 2), 'percentil' => round(self::percentilDeZ($z), 1)];
    }

    /** Valor (kg/cm) correspondente a um escore-z — usado para desenhar a curva de referência. */
    public function valorParaZ(string $tipo, string $sexo, float $idadeMeses, float $z): ?float
    {
        $lms = $this->lmsInterpolado($tipo, $sexo, $idadeMeses);
        if ($lms === null) {
            return null;
        }
        [$l, $m, $s] = $lms;
        return abs($l) > 1e-6
            ? $m * ((1 + $l * $s * $z) ** (1 / $l))
            : $m * exp($s * $z);
    }

    /**
     * Séries prontas para desenhar as curvas de crescimento: referência OMS
     * (P3/P50/P97, amostrada por mês) e os pontos medidos da criança.
     * Usada na ficha essencial E na página pública do pediatra.
     * Sem sexo ou nascimento não há referência → null.
     * @param array<int,array<string,mixed>> $historico linhas de `medicoes`
     * @return ?array<string,array{unidade:string,referencia:array,pontos:array}>
     */
    public function curvas(array $crianca, array $historico): ?array
    {
        $sexo = (string)($crianca['sexo'] ?? '');
        $nascimento = (string)($crianca['data_nascimento'] ?? '');
        if ($nascimento === '' || !in_array($sexo, ['masculino', 'feminino'], true)) {
            return null;
        }
        $idadeAtual = self::idadeEmMeses($nascimento, hoje());
        if ($idadeAtual < 0 || $idadeAtual > 60) {
            return null;
        }
        $limite = (int)min(60, max(12, ceil($idadeAtual) + 3));

        $curvas = [];
        $metricas = [
            'peso' => ['coluna' => 'peso_g', 'unidade' => 'kg', 'fator' => 1000],
            'altura' => ['coluna' => 'altura_mm', 'unidade' => 'cm', 'fator' => 10],
            'pc' => ['coluna' => 'perimetro_cefalico_mm', 'unidade' => 'cm', 'fator' => 10],
        ];
        foreach ($metricas as $tipo => $metrica) {
            $referencia = [];
            foreach (range(0, $limite) as $mes) {
                $referencia[] = [
                    $mes,
                    $this->valorParaZ($tipo, $sexo, (float)$mes, -1.881), // P3
                    $this->valorParaZ($tipo, $sexo, (float)$mes, 0.0),    // P50
                    $this->valorParaZ($tipo, $sexo, (float)$mes, 1.881),  // P97
                ];
            }
            $pontos = [];
            foreach ($historico as $medicao) {
                if ($medicao['status'] !== 'confirmada' || $medicao[$metrica['coluna']] === null) {
                    continue;
                }
                $mes = self::idadeEmMeses($nascimento, (string)$medicao['medido_em']);
                if ($mes >= 0 && $mes <= $limite) {
                    $pontos[] = [round($mes, 2), round((float)$medicao[$metrica['coluna']] / $metrica['fator'], 2)];
                }
            }
            usort($pontos, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
            if ($pontos !== []) {
                $curvas[$tipo] = [
                    'unidade' => $metrica['unidade'],
                    'referencia' => $referencia,
                    'pontos' => $pontos,
                ];
            }
        }
        return $curvas !== [] ? $curvas : null;
    }

    /** @return ?array{0:float,1:float,2:float} */
    private function lmsInterpolado(string $tipo, string $sexo, float $idadeMeses): ?array
    {
        $tabela = $this->tabelas[$tipo][$sexo] ?? null;
        if ($tabela === null || $idadeMeses < 0 || $idadeMeses > 60) {
            return null;
        }
        $base = (int)floor($idadeMeses);
        $fracao = $idadeMeses - $base;
        $inferior = $tabela[$base] ?? null;
        $superior = $tabela[min(60, $base + 1)] ?? $inferior;
        if ($inferior === null) {
            return null;
        }
        return [
            $inferior[0] + ($superior[0] - $inferior[0]) * $fracao,
            $inferior[1] + ($superior[1] - $inferior[1]) * $fracao,
            $inferior[2] + ($superior[2] - $inferior[2]) * $fracao,
        ];
    }

    /** Φ(z)·100 — distribuição normal padrão via aproximação de erf (Abramowitz–Stegun). */
    private static function percentilDeZ(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $probabilidade = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        $phi = $z > 0 ? 1 - $probabilidade : $probabilidade;
        return max(0.1, min(99.9, $phi * 100));
    }
}
