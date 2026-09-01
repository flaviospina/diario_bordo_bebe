<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Core\Identificadores;
use App\Core\MiniPdf;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioSistema;

/**
 * Relatórios e exportações (restritas a responsavel/admin_familia).
 * Arquivos gerados vão para o storage (fora do webroot) e são entregues
 * por /download/{codigo} via arquivos_gerados.
 */
final class ServicoRelatorios extends RepositorioSistema
{
    /**
     * Agregados por dia para os comparativos e o Modo Pediatra.
     * @return array<string,array{sono_min:int,mamadas:int,volume_ml:int,fraldas:int,coco:int,intercorrencias:int}>
     */
    public function agregadosPorDia(int $criancaId, string $de, string $ate): array
    {
        $registros = (new RepositorioRegistros())->linhaDoTempo($criancaId, null, $de, $ate, 500 * 30);
        $dias = [];
        $cursor = new \DateTime($de);
        $fim = new \DateTime($ate);
        while ($cursor <= $fim) {
            $dias[$cursor->format('Y-m-d')] = [
                'sono_min' => 0, 'mamadas' => 0, 'volume_ml' => 0,
                'fraldas' => 0, 'coco' => 0, 'intercorrencias' => 0,
            ];
            $cursor->modify('+1 day');
        }

        foreach ($registros as $registro) {
            $dia = substr((string)$registro['inicio'], 0, 10);
            if (!isset($dias[$dia])) {
                continue;
            }
            $dados = json_decode((string)($registro['dados'] ?? 'null'), true) ?: [];
            switch ($registro['categoria_slug']) {
                case 'amamentacao':
                    $dias[$dia]['mamadas']++;
                    break;
                case 'mamadeira':
                    $dias[$dia]['mamadas']++;
                    $dias[$dia]['volume_ml'] += max(0, (int)($dados['volume_ml'] ?? 0) - (int)($dados['volume_restante_ml'] ?? 0));
                    break;
                case 'soneca':
                case 'sono-noturno':
                    if ($registro['fim'] !== null) {
                        $dias[$dia]['sono_min'] += max(0, (int)((strtotime((string)$registro['fim']) - strtotime((string)$registro['inicio'])) / 60));
                    }
                    break;
                case 'fralda':
                    $dias[$dia]['fraldas']++;
                    if (in_array($dados['conteudo'] ?? '', ['coco', 'ambos'], true)) {
                        $dias[$dia]['coco']++;
                    }
                    break;
            }
            if ($registro['categoria_grupo'] === 'intercorrencia') {
                $dias[$dia]['intercorrencias']++;
            }
        }
        return $dias;
    }

    /** Exporta os registros de um período em CSV. Retorna o codigo_publico do download. */
    public function exportarCsv(array $crianca, string $de, string $ate): string
    {
        $registros = (new RepositorioRegistros())->linhaDoTempo((int)$crianca['id'], null, $de, $ate, 5000);
        $linhas = [['data', 'hora', 'fim', 'categoria', 'grupo', 'status', 'dados', 'observacao', 'registrado_por']];
        foreach (array_reverse($registros) as $registro) {
            $linhas[] = [
                substr((string)$registro['inicio'], 0, 10),
                substr((string)$registro['inicio'], 11, 5),
                $registro['fim'] !== null ? substr((string)$registro['fim'], 11, 5) : '',
                (string)$registro['categoria_nome'],
                (string)$registro['categoria_grupo'],
                (string)$registro['status'],
                (string)($registro['dados'] ?? ''),
                (string)($registro['observacao'] ?? ''),
                (string)$registro['usuario_nome'],
            ];
        }

        $conteudo = "\u{FEFF}"; // BOM: Excel abre UTF-8 corretamente
        $ponteiro = fopen('php://temp', 'r+');
        foreach ($linhas as $linha) {
            fputcsv($ponteiro, $linha, ';', '"', '\\');
        }
        rewind($ponteiro);
        $conteudo .= (string)stream_get_contents($ponteiro);
        fclose($ponteiro);

        return $this->guardarArquivo(
            'csv',
            'diario-' . $crianca['slug'] . '-' . $de . '-a-' . $ate,
            $conteudo
        );
    }

    /** PDF do resumo do dia. Retorna o codigo_publico do download. */
    public function gerarPdfResumo(array $crianca, string $data): string
    {
        $texto = (new ServicoResumo(Autenticacao::familiaId()))->gerarTexto($crianca, $data);
        $pdf = new MiniPdf();
        $pdf->linhaTexto('Diário do Bebê — Resumo do dia', 16, true);
        $pdf->linhaTexto($crianca['nome'] . ' · ' . data_br($data . ' 00:00:00', 'd/m/Y'), 11);
        $pdf->linhaHorizontal();
        $pdf->espaco();
        $pdf->paragrafo($texto, 11);
        return $this->guardarArquivo('pdf', 'resumo-' . $crianca['slug'] . '-' . $data, $pdf->gerar());
    }

    /** PDF do Modo Pediatra: 30 dias em uma página, formatado para consulta. */
    public function gerarPdfPediatra(array $crianca, string $de, string $ate): string
    {
        $dias = $this->agregadosPorDia((int)$crianca['id'], $de, $ate);
        $pdf = new MiniPdf();
        $pdf->linhaTexto('Diário do Bebê — Modo Pediatra', 16, true);
        $pdf->linhaTexto(
            $crianca['nome']
            . ($crianca['data_nascimento'] !== null ? ' · nascimento ' . data_br((string)$crianca['data_nascimento'], 'd/m/Y') : '')
            . ' · período ' . data_br($de . ' 0:0', 'd/m') . ' a ' . data_br($ate . ' 0:0', 'd/m/Y'),
            10
        );
        if (!empty($crianca['alergias'])) {
            $pdf->linhaTexto('Alergias: ' . $crianca['alergias'], 10, true);
        }
        if (!empty($crianca['medicacoes_continuas'])) {
            $pdf->linhaTexto('Medicações contínuas: ' . $crianca['medicacoes_continuas'], 10);
        }

        // Crescimento: últimas medições confirmadas, com percentil congelado (OMS)
        $medicoes = array_values(array_filter(
            (new \App\Repositories\RepositorioMedicoes())->listar((int)$crianca['id'], 6),
            static fn(array $m): bool => $m['status'] === 'confirmada'
        ));
        if ($medicoes !== []) {
            $pdf->linhaHorizontal();
            $posicoesCrescimento = [0.0, 80.0, 190.0, 300.0, 420.0];
            $pdf->linhaTabela(['Medição', 'Peso', 'Altura', 'Per. cefálico', 'Origem'], $posicoesCrescimento, 9, true);
            $formatar = static function (int|string|null $bruto, float $fator, $percentil, string $unidade): string {
                if ($bruto === null) {
                    return '-';
                }
                $texto = number_format((int)$bruto / $fator, $fator > 100 ? 3 : 1, ',', '.') . ' ' . $unidade;
                return $percentil !== null ? $texto . ' (P' . number_format((float)$percentil, 0) . ')' : $texto;
            };
            foreach ($medicoes as $medicao) {
                $pdf->linhaTabela([
                    data_br($medicao['medido_em'] . ' 0:0', 'd/m/Y'),
                    $formatar($medicao['peso_g'], 1000, $medicao['percentil_peso'], 'kg'),
                    $formatar($medicao['altura_mm'], 10, $medicao['percentil_altura'], 'cm'),
                    $formatar($medicao['perimetro_cefalico_mm'], 10, $medicao['percentil_pc'], 'cm'),
                    $medicao['origem'] === 'pediatra' ? 'consultório' : 'casa',
                ], $posicoesCrescimento, 8.5);
            }
        }
        $pdf->linhaHorizontal();

        $posicoes = [0.0, 90.0, 180.0, 270.0, 360.0, 440.0];
        $pdf->linhaTabela(['Dia', 'Sono (h)', 'Mamadas', 'Volume (ml)', 'Fraldas', 'Intercorr.'], $posicoes, 9, true);
        $totais = ['sono' => 0, 'mamadas' => 0, 'volume' => 0, 'fraldas' => 0, 'interc' => 0];
        $diasSemana = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
        $diasComDado = 0;
        foreach ($dias as $dia => $valores) {
            $temDado = array_sum($valores) > 0;
            if ($temDado) {
                $diasComDado++;
            }
            $pdf->linhaTabela([
                data_br($dia . ' 0:0', 'd/m') . ' ' . $diasSemana[(int)date('w', (int)strtotime($dia))],
                $temDado ? number_format($valores['sono_min'] / 60, 1, ',', '') : '-',
                $temDado ? (string)$valores['mamadas'] : '-',
                $temDado ? (string)$valores['volume_ml'] : '-',
                $temDado ? $valores['fraldas'] . ($valores['coco'] > 0 ? ' (' . $valores['coco'] . ' c/ cocô)' : '') : '-',
                (string)($valores['intercorrencias'] ?: ''),
            ], $posicoes, 8.5);
            $totais['sono'] += $valores['sono_min'];
            $totais['mamadas'] += $valores['mamadas'];
            $totais['volume'] += $valores['volume_ml'];
            $totais['fraldas'] += $valores['fraldas'];
            $totais['interc'] += $valores['intercorrencias'];
        }
        $pdf->linhaHorizontal();
        $divisor = max(1, $diasComDado);
        $pdf->linhaTabela([
            'Média/dia',
            number_format($totais['sono'] / 60 / $divisor, 1, ',', ''),
            number_format($totais['mamadas'] / $divisor, 1, ',', ''),
            (string)(int)round($totais['volume'] / $divisor),
            number_format($totais['fraldas'] / $divisor, 1, ',', ''),
            'total ' . $totais['interc'],
        ], $posicoes, 9, true);
        $pdf->espaco();
        $pdf->linhaTexto('Gerado em ' . data_br(agora()) . ' — dias sem dado aparecem como "—".', 8);

        return $this->guardarArquivo('pdf', 'pediatra-' . $crianca['slug'] . '-' . $ate, $pdf->gerar());
    }

    /** Exportação LGPD: todos os dados da família em JSON. */
    public function exportarDadosFamiliaJson(): string
    {
        $familiaId = Autenticacao::familiaId();
        $tabelas = [
            'familias' => 'SELECT * FROM familias WHERE id = ?',
            'usuarios' => 'SELECT id, codigo_publico, nome, email, papel, telefone_whatsapp, ativo, ultimo_login, criado_em FROM usuarios WHERE familia_id = ?',
            'criancas' => 'SELECT * FROM criancas WHERE familia_id = ?',
            'configuracoes' => 'SELECT chave, valor, atualizado_em FROM configuracoes_familia WHERE familia_id = ?',
            'roteiro_blocos' => 'SELECT * FROM roteiro_blocos WHERE familia_id = ?',
            'registros' => 'SELECT * FROM registros WHERE familia_id = ?',
            'intercorrencias' => 'SELECT * FROM intercorrencias WHERE familia_id = ?',
            'turnos' => 'SELECT * FROM turnos WHERE familia_id = ?',
            'suprimentos' => 'SELECT * FROM suprimentos WHERE familia_id = ?',
            'resumos_diarios' => 'SELECT * FROM resumos_diarios WHERE familia_id = ?',
            'solicitacoes_edicao' => 'SELECT * FROM solicitacoes_edicao WHERE familia_id = ?',
        ];
        $exportacao = ['gerado_em' => agora(), 'formato' => 'diariobebe-exportacao-v1'];
        foreach ($tabelas as $nome => $sql) {
            $declaracao = $this->bd->prepare($sql);
            $declaracao->execute([$familiaId]);
            $exportacao[$nome] = $declaracao->fetchAll();
        }
        // Exportação completa é um direito do titular, mas o hash de senha não sai
        return $this->guardarArquivo(
            'csv', // servido como texto; extensão do download vem da descrição
            'exportacao-lgpd-' . hoje(),
            json_encode($exportacao, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}'
        );
    }

    private function guardarArquivo(string $tipo, string $descricao, string $conteudo): string
    {
        $codigo = Identificadores::codigoPublico();
        $pasta = STORAGE_PATH . '/exportacoes/' . Autenticacao::familiaId();
        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }
        $relativo = 'exportacoes/' . Autenticacao::familiaId() . '/' . $codigo . '.' . $tipo;
        file_put_contents(STORAGE_PATH . '/' . $relativo, $conteudo);

        $this->executar(
            'INSERT INTO arquivos_gerados (codigo_publico, familia_id, tipo, descricao, caminho, gerado_por, expira_em)
             VALUES (:codigo, :familia, :tipo, :descricao, :caminho, :usuario, DATE_ADD(NOW(), INTERVAL 7 DAY))',
            [
                'codigo' => $codigo,
                'familia' => Autenticacao::familiaId(),
                'tipo' => $tipo,
                'descricao' => mb_substr($descricao, 0, 160),
                'caminho' => $relativo,
                'usuario' => Autenticacao::id(),
            ]
        );
        return $codigo;
    }
}
