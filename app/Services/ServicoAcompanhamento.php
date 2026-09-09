<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Ambiente;
use App\Core\ClienteClaude;
use App\Repositories\RepositorioAnalisesIa;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioFamilias;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Acompanhamento com IA (Rodada 2): compila um resumo ESTRUTURADO e ANÔNIMO
 * dos últimos 14 dias do diário (idade e sexo — nunca nome, fotos ou textos
 * livres da família) e pede à IA de 2 a 4 observações tipadas:
 *   padrao     — padrão notado na rotina;
 *   preventivo — algo que merece atenção e conversa com o pediatra;
 *   fase       — contexto da fase/idade (saltos, marcos esperados);
 *   celebracao — conquista para comemorar.
 *
 * Guardas fixas: a IA NUNCA diagnostica nem prescreve — toda observação de
 * atenção aponta o pediatra. Sem chave de API: em desenvolvimento entra o
 * modo simulado (fluxo testável de ponta a ponta); em produção a página
 * explica como ativar. O resumo enviado fica guardado em analises_ia
 * (dados_base) — auditável a qualquer momento.
 */
final class ServicoAcompanhamento
{
    public const PERIODO_DIAS = 14;
    private const TIPOS_VALIDOS = ['padrao', 'preventivo', 'fase', 'celebracao'];
    private const MINIMO_REGISTROS = 5;

    public function __construct(private readonly ?int $familiaId = null)
    {
    }

    // ── Geração de uma análise ────────────────────────────────

    /**
     * @return array{erro:?string, analise_id:?int}
     */
    public function gerar(array $crianca, string $origem = 'manual'): array
    {
        $analises = new RepositorioAnalisesIa($this->familiaId);
        $criancaId = (int)$crianca['id'];

        // Uma análise por dia por criança: custo sob controle e sem spam
        $ultima = $analises->ultimaAnalise($criancaId);
        if ($ultima !== null && substr((string)$ultima['gerado_em'], 0, 10) === hoje()) {
            return ['erro' => 'Já existe uma análise de hoje. A próxima pode ser gerada amanhã.', 'analise_id' => (int)$ultima['id']];
        }

        $ate = hoje();
        $de = date('Y-m-d', strtotime('-' . (self::PERIODO_DIAS - 1) . ' days'));
        $dadosBase = $this->compilarDadosBase($crianca, $de, $ate);
        if ($dadosBase['periodo']['total_registros'] < self::MINIMO_REGISTROS) {
            return ['erro' => 'Ainda há poucos registros nos últimos ' . self::PERIODO_DIAS
                . ' dias para uma análise útil. Continue registrando o dia a dia.', 'analise_id' => null];
        }

        $cliente = ClienteClaude::daConfiguracao();
        if ($cliente === null && !Ambiente::ehDesenvolvimento()) {
            return ['erro' => 'A análise por IA ainda não está ativa: falta configurar a chave '
                . 'ANTHROPIC_API_KEY no arquivo .env do servidor.', 'analise_id' => null];
        }

        try {
            if ($cliente !== null) {
                $resposta = $cliente->perguntar(
                    self::instrucaoDeSistema(),
                    json_encode($dadosBase, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}'
                );
                $observacoes = $this->extrairObservacoes($resposta);
                $modelo = $cliente->modelo();
            } else {
                $observacoes = $this->observacoesSimuladas($dadosBase);
                $modelo = 'simulado-dev';
            }
        } catch (\Throwable $excecao) {
            return ['erro' => $excecao->getMessage(), 'analise_id' => null];
        }
        if ($observacoes === []) {
            return ['erro' => 'A IA não devolveu observações válidas. Tente de novo mais tarde.', 'analise_id' => null];
        }

        $analiseId = $analises->criarAnalise($criancaId, $de, $ate, $origem, $modelo, $dadosBase);
        foreach ($observacoes as $ordem => $observacao) {
            $analises->adicionarObservacao(
                $analiseId,
                $criancaId,
                $observacao['tipo'],
                $observacao['titulo'],
                $observacao['texto'],
                $ordem + 1
            );
        }
        return ['erro' => null, 'analise_id' => $analiseId];
    }

    // ── Tarefa semanal (cron, sem sessão) ─────────────────────

    /**
     * Gera a análise automática de cada criança ativa cuja última análise tem
     * mais de 7 dias (ou nunca houve) e avisa os responsáveis por e-mail.
     * @return array{analises_geradas:array<int,int>, avisos:array<string,string>}
     */
    public function gerarSemanais(): array
    {
        $geradas = [];
        $avisos = [];
        foreach ((new RepositorioFamilias())->listarAtivas() as $familia) {
            $familiaId = (int)$familia['id'];
            foreach ((new RepositorioCriancas($familiaId))->listar() as $crianca) {
                $analises = new RepositorioAnalisesIa($familiaId);
                $ultima = $analises->ultimaAnalise((int)$crianca['id']);
                if ($ultima !== null && strtotime((string)$ultima['gerado_em']) > strtotime('-7 days')) {
                    continue; // ainda dentro da semana da última análise
                }
                $resultado = (new self($familiaId))->gerar($crianca, 'automatica');
                if ($resultado['erro'] !== null) {
                    $avisos[$familiaId . ':' . $crianca['slug']] = $resultado['erro'];
                    continue;
                }
                $geradas[(int)$crianca['id']] = (int)$resultado['analise_id'];
                $this->avisarResponsaveis($familiaId, $crianca, (int)$resultado['analise_id']);
            }
        }
        return ['analises_geradas' => $geradas, 'avisos' => $avisos];
    }

    /** E-mail curto com os títulos das observações e o botão para a página. */
    private function avisarResponsaveis(int $familiaId, array $crianca, int $analiseId): void
    {
        $analise = null;
        foreach ((new RepositorioAnalisesIa($familiaId))->listarComObservacoes((int)$crianca['id'], 1) as $linha) {
            if ((int)$linha['id'] === $analiseId) {
                $analise = $linha;
            }
        }
        if ($analise === null || $analise['observacoes'] === []) {
            return;
        }
        $nome = (string)($crianca['apelido'] ?: $crianca['nome']);
        $itensHtml = '';
        $itensTexto = '';
        foreach ($analise['observacoes'] as $observacao) {
            $itensHtml .= '<li style="margin:0 0 8px;"><strong>' . e((string)$observacao['titulo']) . '</strong></li>';
            $itensTexto .= '- ' . $observacao['titulo'] . "\n";
        }
        $url = url_absoluta('relatorios.acompanhamento') . '?crianca=' . $crianca['slug'];
        $conteudo = '<h2 style="margin:0 0 12px; font-size:19px;">O acompanhamento de ' . e($nome) . ' desta semana está pronto 🌱</h2>'
            . '<p style="margin:0 0 12px;">A análise dos últimos ' . self::PERIODO_DIAS . ' dias do diário trouxe '
            . count($analise['observacoes']) . ' observações:</p>'
            . '<ul style="margin:0 0 4px; padding-left:20px;">' . $itensHtml . '</ul>'
            . ServicoEmail::botao('Ver o acompanhamento completo', $url)
            . '<p style="margin:12px 0 0; font-size:13px; color:#A8A296;">As observações são geradas por IA a partir '
            . 'dos registros do diário e não substituem a avaliação do pediatra.</p>';
        $texto = "O acompanhamento de {$nome} desta semana está pronto.\n\n{$itensTexto}\nVeja em: {$url}\n\n"
            . 'As observações são geradas por IA e não substituem a avaliação do pediatra.';

        $email = new ServicoEmail();
        foreach ((new RepositorioUsuariosFamilia($familiaId))->responsaveisParaNotificar() as $responsavel) {
            if ((string)$responsavel['email'] !== '') {
                $email->enviarHtml(
                    (string)$responsavel['email'],
                    'Acompanhamento da semana de ' . $nome . ' — Diário do Bebê',
                    $conteudo,
                    $texto,
                    'acompanhamento_ia',
                    $analiseId,
                    $familiaId
                );
            }
        }
    }

    // ── Compilação do resumo anônimo ──────────────────────────

    /**
     * Resumo estruturado do período — SEM nome, fotos ou observações livres.
     * @return array<string,mixed>
     */
    public function compilarDadosBase(array $crianca, string $de, string $ate): array
    {
        $registros = (new RepositorioRegistros($this->familiaId))
            ->linhaDoTempo((int)$crianca['id'], null, $de, $ate, 5000);
        $totalDias = max(1, (int)((strtotime($ate) - strtotime($de)) / 86400) + 1);
        $meioDoPeriodo = date('Y-m-d', strtotime($de . ' +' . intdiv($totalDias, 2) . ' days'));

        $vazio = ['mamadas' => 0, 'volume_ml' => 0, 'sono_min' => 0, 'colicas' => 0, 'fraldas' => 0];
        $metades = ['primeira' => $vazio, 'segunda' => $vazio];
        $dias = [];
        $mamadasPeito = 0;
        $mamadeiras = 0;
        $volumeMl = 0;
        $sonoMin = 0;
        $sonecas = 0;
        $despertares = 0;
        $dificuldadeDormir = 0;
        $vomitos = 0;
        $regurgitacoes = 0;
        $colicas = 0;
        $diasComColica = [];
        $colicaMinutos = [];
        $aliviou = [];
        $choroProlongado = 0;
        $fraldas = 0;
        $coco = 0;
        $medicacoes = [];
        $sintomas = [];
        $temperaturas = 0;
        $intercorrencias = [];
        $marcos = [];

        foreach ($registros as $registro) {
            $dia = substr((string)$registro['inicio'], 0, 10);
            $dias[$dia] = true;
            $metade = $dia < $meioDoPeriodo ? 'primeira' : 'segunda';
            $dados = json_decode((string)($registro['dados'] ?? 'null'), true) ?: [];
            $duracaoMin = $registro['fim'] !== null
                ? max(0, (int)((strtotime((string)$registro['fim']) - strtotime((string)$registro['inicio'])) / 60))
                : null;

            switch ($registro['categoria_slug']) {
                case 'amamentacao':
                    $mamadasPeito++;
                    $metades[$metade]['mamadas']++;
                    break;
                case 'mamadeira':
                case 'formula-preparada':
                    $mamadeiras++;
                    $metades[$metade]['mamadas']++;
                    $consumido = max(0, (int)($dados['volume_ml'] ?? 0) - (int)($dados['volume_restante_ml'] ?? 0));
                    $volumeMl += $consumido;
                    $metades[$metade]['volume_ml'] += $consumido;
                    break;
                case 'soneca':
                case 'sono-noturno':
                    if ($registro['categoria_slug'] === 'soneca') {
                        $sonecas++;
                    }
                    if ($duracaoMin !== null) {
                        $sonoMin += $duracaoMin;
                        $metades[$metade]['sono_min'] += $duracaoMin;
                    }
                    break;
                case 'despertar':
                    $despertares++;
                    break;
                case 'dificuldade-dormir':
                    $dificuldadeDormir++;
                    break;
                case 'vomito':
                    $vomitos++;
                    break;
                case 'regurgitacao':
                    $regurgitacoes++;
                    break;
                case 'colica':
                    $colicas++;
                    $metades[$metade]['colicas']++;
                    $diasComColica[$dia] = true;
                    if (isset($dados['duracao_min'])) {
                        $colicaMinutos[] = (int)$dados['duracao_min'];
                    }
                    if (!empty($dados['o_que_aliviou'])) {
                        $aliviou[] = mb_substr((string)$dados['o_que_aliviou'], 0, 60);
                    }
                    break;
                case 'choro-prolongado':
                    $choroProlongado++;
                    break;
                case 'fralda':
                    $fraldas++;
                    $metades[$metade]['fraldas']++;
                    if (in_array($dados['conteudo'] ?? '', ['coco', 'ambos'], true)) {
                        $coco++;
                    }
                    break;
                case 'medicacao':
                    $nomeMedicacao = mb_substr(trim((string)($dados['nome'] ?? 'não informado')), 0, 60);
                    $medicacoes[$nomeMedicacao] = ($medicacoes[$nomeMedicacao] ?? 0) + 1;
                    break;
                case 'sintoma':
                    $tipoSintoma = mb_substr((string)($dados['tipo'] ?? 'outro'), 0, 60);
                    $sintomas[$tipoSintoma] = ($sintomas[$tipoSintoma] ?? 0) + 1;
                    break;
                case 'temperatura':
                    $temperaturas++;
                    break;
                case 'marco-desenvolvimento':
                    $marcos[] = [
                        'descricao' => mb_substr((string)($dados['descricao'] ?? ($registro['observacao'] ?? 'marco registrado')), 0, 160),
                        'dias_atras' => (int)((strtotime($ate) - strtotime($dia)) / 86400),
                    ];
                    break;
            }
            if ($registro['categoria_grupo'] === 'intercorrencia') {
                $intercorrencias[] = [
                    'tipo' => (string)$registro['categoria_nome'],
                    'gravidade' => (string)($dados['gravidade'] ?? 'leve'),
                    'dias_atras' => (int)((strtotime($ate) - strtotime($dia)) / 86400),
                ];
            }
        }

        $diasComRegistro = max(1, count($dias));
        $mediaDia = static fn(int|float $total): float => round($total / $diasComRegistro, 1);
        $diasMetade = max(1, intdiv($totalDias, 2));
        $resumoMetade = static fn(array $m) => [
            'mamadas_por_dia' => round($m['mamadas'] / $diasMetade, 1),
            'volume_mamadeira_ml_por_dia' => (int)round($m['volume_ml'] / $diasMetade),
            'sono_min_por_dia' => (int)round($m['sono_min'] / $diasMetade),
            'colicas_total' => $m['colicas'],
            'fraldas_por_dia' => round($m['fraldas'] / $diasMetade, 1),
        ];

        $nascimento = (string)($crianca['data_nascimento'] ?? '');
        return [
            'bebe' => [
                'idade' => $nascimento !== '' ? ServicoCrescimento::idadeFormatada($nascimento) : 'não informada',
                'idade_meses' => $nascimento !== '' ? round(ServicoCrescimento::idadeEmMeses($nascimento, $ate), 1) : null,
                'sexo' => (string)($crianca['sexo'] ?? 'não informado'),
            ],
            'periodo' => [
                'de' => $de,
                'ate' => $ate,
                'dias' => $totalDias,
                'dias_com_registro' => count($dias),
                'total_registros' => count($registros),
            ],
            'alimentacao' => [
                'mamadas_no_peito_total' => $mamadasPeito,
                'mamadeiras_total' => $mamadeiras,
                'mamadas_por_dia_media' => $mediaDia($mamadasPeito + $mamadeiras),
                'volume_mamadeira_ml_por_dia_media' => (int)$mediaDia($volumeMl),
                'vomitos_total' => $vomitos,
                'regurgitacoes_total' => $regurgitacoes,
            ],
            'sono' => [
                'total_min_por_dia_media' => (int)$mediaDia($sonoMin),
                'sonecas_por_dia_media' => $mediaDia($sonecas),
                'despertares_noturnos_total' => $despertares,
                'dificuldade_para_dormir_total' => $dificuldadeDormir,
            ],
            'desconforto' => [
                'colicas_total' => $colicas,
                'dias_com_colica' => count($diasComColica),
                'colica_duracao_media_min' => $colicaMinutos !== []
                    ? (int)round(array_sum($colicaMinutos) / count($colicaMinutos)) : null,
                'o_que_aliviou_colica' => array_slice(array_values(array_unique($aliviou)), 0, 5),
                'choro_prolongado_total' => $choroProlongado,
            ],
            'fraldas' => [
                'trocas_por_dia_media' => $mediaDia($fraldas),
                'coco_por_dia_media' => $mediaDia($coco),
            ],
            'saude' => [
                'medicacoes_vezes' => $medicacoes,
                'sintomas_vezes' => $sintomas,
                'registros_de_temperatura' => $temperaturas,
                'intercorrencias' => array_slice($intercorrencias, 0, 10),
            ],
            'marcos_de_desenvolvimento' => array_slice($marcos, 0, 10),
            'comparacao_entre_metades_do_periodo' => [
                'primeira_metade' => $resumoMetade($metades['primeira']),
                'segunda_metade' => $resumoMetade($metades['segunda']),
            ],
        ];
    }

    // ── Prompt e parse ────────────────────────────────────────

    private static function instrucaoDeSistema(): string
    {
        return <<<TEXTO
Você é o assistente de acompanhamento do "Diário do Bebê", um aplicativo brasileiro em que a babá e os pais registram a rotina do bebê. Você recebe um resumo ESTRUTURADO e ANÔNIMO (idade e sexo, nunca o nome) dos últimos dias do diário e devolve observações úteis para os pais.

REGRAS OBRIGATÓRIAS:
1. Você NÃO é médico. NUNCA dê diagnóstico, NUNCA indique, sugira ou dose medicamento, NUNCA afirme que algo "é" uma doença ou condição.
2. Quando um dado merecer atenção, descreva o padrão observado e oriente SEMPRE a conversar com o pediatra — quem avalia e decide é o pediatra.
3. Baseie-se APENAS nos números recebidos. Não invente dados nem suponha o que não está no resumo.
4. Considere a idade do bebê para dar contexto de fase (saltos de desenvolvimento, janelas de sono, introdução alimentar), sempre como informação geral, não como avaliação individual.
5. Tom acolhedor, claro e direto, em português do Brasil. Cada texto com 2 a 4 frases. Nada de alarmismo.
6. Use a comparação entre as metades do período para apontar tendências (melhora ou mudança), quando fizer sentido.

FORMATO DA RESPOSTA — responda APENAS com um array JSON válido, sem nenhum texto fora dele, com 2 a 4 itens:
[{"tipo": "padrao|preventivo|fase|celebracao", "titulo": "título curto (até 80 caracteres)", "texto": "2 a 4 frases"}]

Significado dos tipos: "padrao" = padrão notado na rotina; "preventivo" = merece atenção e conversa com o pediatra; "fase" = contexto da fase/idade; "celebracao" = conquista para comemorar. Inclua no máximo 1 item "celebracao". Se houver marcos de desenvolvimento no resumo, celebre-os.
TEXTO;
    }

    /**
     * @return array<int,array{tipo:string,titulo:string,texto:string}>
     */
    private function extrairObservacoes(string $resposta): array
    {
        // Aceita a resposta com ou sem cerca de código; extrai o primeiro array JSON
        $inicio = strpos($resposta, '[');
        $fim = strrpos($resposta, ']');
        if ($inicio === false || $fim === false || $fim <= $inicio) {
            return [];
        }
        $itens = json_decode(substr($resposta, $inicio, $fim - $inicio + 1), true);
        if (!is_array($itens)) {
            return [];
        }
        $observacoes = [];
        foreach ($itens as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tipo = (string)($item['tipo'] ?? '');
            $titulo = trim((string)($item['titulo'] ?? ''));
            $texto = trim((string)($item['texto'] ?? ''));
            if (!in_array($tipo, self::TIPOS_VALIDOS, true) || $titulo === '' || $texto === '') {
                continue;
            }
            $observacoes[] = ['tipo' => $tipo, 'titulo' => $titulo, 'texto' => $texto];
            if (count($observacoes) === 4) {
                break;
            }
        }
        return $observacoes;
    }

    /**
     * Modo simulado (desenvolvimento sem chave): observações determinísticas a
     * partir dos mesmos dados compilados — o fluxo inteiro é testável.
     * @return array<int,array{tipo:string,titulo:string,texto:string}>
     */
    private function observacoesSimuladas(array $dadosBase): array
    {
        $observacoes = [[
            'tipo' => 'padrao',
            'titulo' => '[SIMULADO] Rotina com ' . $dadosBase['alimentacao']['mamadas_por_dia_media'] . ' mamadas por dia',
            'texto' => 'Nos últimos ' . $dadosBase['periodo']['dias'] . ' dias houve registros em '
                . $dadosBase['periodo']['dias_com_registro'] . ' dias, com média de '
                . $dadosBase['alimentacao']['mamadas_por_dia_media'] . ' mamadas e '
                . $dadosBase['sono']['total_min_por_dia_media'] . ' minutos de sono por dia. '
                . 'Este é o modo simulado: configure a chave da API para a análise real.',
        ]];
        if ($dadosBase['desconforto']['colicas_total'] > 0) {
            $observacoes[] = [
                'tipo' => 'preventivo',
                'titulo' => '[SIMULADO] Cólicas em ' . $dadosBase['desconforto']['dias_com_colica'] . ' dias do período',
                'texto' => 'Foram ' . $dadosBase['desconforto']['colicas_total'] . ' episódios de cólica. '
                    . 'Vale levar esse padrão para a próxima conversa com o pediatra.',
            ];
        }
        if ($dadosBase['marcos_de_desenvolvimento'] !== []) {
            $observacoes[] = [
                'tipo' => 'celebracao',
                'titulo' => '[SIMULADO] Marco de desenvolvimento registrado 🎉',
                'texto' => 'O diário registrou: "' . $dadosBase['marcos_de_desenvolvimento'][0]['descricao'] . '". Que fase boa!',
            ];
        }
        return $observacoes;
    }
}
