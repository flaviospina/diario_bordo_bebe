<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioCardapio;
use App\Repositories\RepositorioUsuariosFamilia;

/**
 * Cardápio semanal + lista de compras (Rodada 6).
 *
 *  - Os PAIS montam o cardápio da semana (fruta da manhã, fruta da tarde,
 *    almoço e, quando houver, jantar) e a lista de compras; a semana-modelo
 *    (app/Dados/cardapio_modelo_semana1.php) é só o ponto de partida.
 *  - A CUIDADORA vê o cardápio do dia no Meu Dia, marca o que colocou no
 *    carrinho no supermercado e registra o almoço (com foto obrigatória)
 *    já pré-preenchido pelo cardápio.
 *  - Comprar ≠ consumir: marcar um item nunca cria refeição, evento médico
 *    ou notificação.
 */
final class ServicoCardapio
{
    public const TAMANHO_MAXIMO_HTML = 2 * 1024 * 1024;

    public function __construct(private readonly ?int $familiaId = null)
    {
    }

    /** @return array<string,mixed> o modelo padrão da semana 1 */
    public static function modelo(): array
    {
        return require RAIZ_PROJETO . '/app/Dados/cardapio_modelo_semana1.php';
    }

    // ── Criação de semanas ────────────────────────────────────

    /**
     * Carrega a semana-modelo (02–08/11/2026) para a criança — idempotente:
     * se já existir uma semana com essa data de início, apenas a devolve
     * (itens e marcações existentes são preservados).
     * @return array{semana_id:int, criada:bool}
     */
    public function carregarModelo(int $criancaId): array
    {
        $modelo = self::modelo();
        return $this->criarSemana($criancaId, $modelo['titulo'], $modelo['inicio'], $modelo['fim'], 'modelo', $modelo['descricao'], $modelo['itens'], $modelo['dias']);
    }

    /**
     * Nova semana a partir de uma data de início. Com $doModelo, copia os
     * itens (todos DESMARCADOS) e o padrão de cardápio por dia da semana —
     * os pais revisam antes de usar. Sem $doModelo, nasce vazia.
     * @return array{semana_id:int, criada:bool}
     */
    public function novaSemana(int $criancaId, string $inicio, bool $doModelo): array
    {
        $fim = date('Y-m-d', strtotime($inicio . ' +6 days'));
        $rotulo = 'Semana de ' . data_br($inicio . ' 00:00:00', 'd/m') . ' a ' . data_br($fim . ' 00:00:00', 'd/m/Y');
        $itens = [];
        $dias = [];
        if ($doModelo) {
            $modelo = self::modelo();
            $itens = array_map(static fn(array $i): array => $i + ['nota' => null], $modelo['itens']);
            foreach ($modelo['dias'] as $indice => $dia) {
                $dias[] = ['data' => date('Y-m-d', strtotime($inicio . " +{$indice} days"))] + array_diff_key($dia, ['data' => 1]);
            }
        } else {
            for ($i = 0; $i < 7; $i++) {
                $dias[] = ['data' => date('Y-m-d', strtotime($inicio . " +{$i} days")), 'fruta_manha' => null, 'fruta_tarde' => null, 'almoco' => null, 'jantar' => null];
            }
        }
        return $this->criarSemana($criancaId, $rotulo, $inicio, $fim, $doModelo ? 'modelo' : 'manual',
            $doModelo ? 'Copiada do modelo — revise o cardápio e as quantidades.' : null, $itens, $dias);
    }

    /** @return array{semana_id:int, criada:bool} */
    private function criarSemana(int $criancaId, string $titulo, string $inicio, string $fim, string $origem, ?string $descricao, array $itens, array $dias): array
    {
        $repositorio = new RepositorioCardapio($this->familiaId);
        $existente = $repositorio->semanaPorInicio($criancaId, $inicio);
        if ($existente !== null) {
            return ['semana_id' => (int)$existente['id'], 'criada' => false];
        }
        $semanaId = $repositorio->criarSemana($criancaId, $titulo, $inicio, $fim, $origem, $descricao,
            Autenticacao::estaLogado() ? Autenticacao::id() : null);
        foreach ($itens as $item) {
            $repositorio->adicionarItem($semanaId, (string)$item['stable_id'], (string)$item['categoria'], (string)$item['nome'],
                $item['quantidade'] ?? null, $item['nota'] ?? null, (int)($item['ordem'] ?? 1));
        }
        foreach ($dias as $dia) {
            $repositorio->salvarDia($semanaId, (string)$dia['data'], $dia['fruta_manha'] ?? null, $dia['fruta_tarde'] ?? null,
                $dia['almoco'] ?? null, $dia['jantar'] ?? null,
                self::slugsDoTexto((string)($dia['almoco'] ?? '')), self::slugsDoTexto((string)($dia['jantar'] ?? '')));
        }
        return ['semana_id' => $semanaId, 'criada' => true];
    }

    // ── Planejamento alimentar importado (Rodada 8) ───────────

    /** Planejamento 09–30/11/2026 enviado pela família (dados em app/Dados). */
    public static function planejamento(): array
    {
        return require RAIZ_PROJETO . '/app/Dados/planejamento_zuno_nov2026.php';
    }

    /** Valida a fonte: datas consecutivas, 4 refeições/dia, totais e composição dos pratos. @return string[] erros */
    public static function validarPlanejamento(array $plano): array
    {
        $erros = [];
        $dias = $plano['dias'] ?? [];
        $esperado = $plano['totais'] ?? [];
        if (count($dias) !== (int)($esperado['dias'] ?? count($dias))) {
            $erros[] = 'Quantidade de dias diferente do esperado.';
        }
        $anterior = null;
        $frutas = $almocos = $jantares = $ovo = $peixe = 0;
        foreach ($dias as $dia) {
            $data = (string)($dia['data'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
                $erros[] = "Data inválida: {$data}";
                continue;
            }
            if ($anterior !== null && date('Y-m-d', strtotime($anterior . ' +1 day')) !== $data) {
                $erros[] = "Datas não consecutivas entre {$anterior} e {$data}.";
            }
            $anterior = $data;
            $refeicoes = $dia['refeicoes'] ?? [];
            if (array_keys($refeicoes) !== ['fruta_manha', 'almoco', 'fruta_tarde', 'jantar']) {
                $erros[] = "{$data}: as 4 refeições (fruta da manhã, almoço, fruta da tarde, jantar) não estão completas.";
                continue;
            }
            foreach ($refeicoes as $slot => $r) {
                if (trim((string)($r['descricao'] ?? '')) === '' || ($r['alimentos'] ?? []) === []) {
                    $erros[] = "{$data}/{$slot}: refeição sem descrição ou alimentos.";
                }
                $grupos = array_column($r['alimentos'] ?? [], 'grupo');
                $nomes = mb_strtolower(implode(' ', array_column($r['alimentos'] ?? [], 'nome')));
                if (in_array($slot, ['almoco', 'jantar'], true)) {
                    $slot === 'almoco' ? $almocos++ : $jantares++;
                    foreach (['cereal_ou_tuberculo' => 'cereal/tubérculo', 'leguminosa' => 'leguminosa', 'proteina' => 'proteína'] as $g => $rotulo) {
                        if (!in_array($g, $grupos, true)) {
                            $erros[] = "{$data}/{$slot}: falta {$rotulo}.";
                        }
                    }
                    if (!in_array('legume', $grupos, true) && !in_array('verdura', $grupos, true)) {
                        $erros[] = "{$data}/{$slot}: falta hortaliça.";
                    }
                    if (str_contains($nomes, 'ovo')) { $ovo++; }
                    if (str_contains($nomes, 'peixe')) { $peixe++; }
                } else {
                    $frutas++;
                }
            }
        }
        foreach (['frutas' => $frutas, 'almocos' => $almocos, 'jantares' => $jantares, 'com_ovo' => $ovo, 'com_peixe' => $peixe] as $chave => $valor) {
            if (isset($esperado[$chave]) && (int)$esperado[$chave] !== $valor) {
                $erros[] = "Total de {$chave} = {$valor}, esperado {$esperado[$chave]}.";
            }
        }
        return $erros;
    }

    /**
     * Importa o planejamento para a criança — idempotente e sem sobrescrever
     * edição manual: cada refeição é classificada como criar (vazia), idêntica,
     * atualizar (valor da importação anterior ou copiado do modelo) ou conflito
     * (texto escrito pelos pais — preservado, salvo se $sobrescreverManuais).
     * Com $aplicar = false é só a prévia (dry-run). Nunca toca compras, itens
     * marcados, registros do diário ou datas fora do intervalo.
     */
    public function importarPlanejamento(int $criancaId, array $plano, bool $aplicar, bool $sobrescreverManuais = false): array
    {
        $erros = self::validarPlanejamento($plano);
        if ($erros !== []) {
            return ['erros' => $erros];
        }
        $repositorio = new RepositorioCardapio($this->familiaId);
        $modelo = self::modelo();
        $slots = ['fruta_manha', 'almoco', 'fruta_tarde', 'jantar'];
        $resumo = ['criar' => 0, 'identicos' => 0, 'atualizar' => 0, 'conflitos' => 0, 'sobrescritos' => 0,
                   'semanas_novas' => [], 'linhas' => [], 'erros' => []];
        $porSemana = [];
        foreach ($plano['dias'] as $dia) {
            $data = (string)$dia['data'];
            $segunda = date('Y-m-d', strtotime($data . ' -' . ((int)date('N', strtotime($data)) - 1) . ' days'));
            $porSemana[$segunda][$data] = $dia;
        }
        $bd = \App\Core\BancoDados::conexao();
        if ($aplicar) {
            $bd->beginTransaction();
        }
        try {
            foreach ($porSemana as $segunda => $diasPlanejados) {
                $semana = $repositorio->semanaPorInicio($criancaId, $segunda);
                $fim = date('Y-m-d', strtotime($segunda . ' +6 days'));
                $semanaId = $semana !== null ? (int)$semana['id'] : 0;
                if ($semana === null) {
                    $resumo['semanas_novas'][] = data_br($segunda . ' 00:00:00', 'd/m') . '–' . data_br($fim . ' 00:00:00', 'd/m/Y');
                    if ($aplicar) {
                        $semanaId = $repositorio->criarSemana($criancaId, 'Planejamento ' . data_br($segunda . ' 00:00:00', 'd/m') . ' a ' . data_br($fim . ' 00:00:00', 'd/m/Y'),
                            $segunda, $fim, 'modelo', 'Cardápio planejado (' . (string)$plano['dataset_id'] . ') — o consumo real é registrado no diário.',
                            Autenticacao::estaLogado() ? Autenticacao::id() : null);
                        for ($i = 0; $i < 7; $i++) {
                            $d = date('Y-m-d', strtotime($segunda . " +{$i} days"));
                            $repositorio->salvarDia($semanaId, $d, null, null, null, null, [], []);
                        }
                    }
                }
                $existentes = [];
                if ($semanaId > 0) {
                    foreach ($repositorio->diasDaSemana($semanaId) as $linha) {
                        $existentes[(string)$linha['data']] = $linha;
                    }
                }
                foreach ($diasPlanejados as $data => $dia) {
                    $atual = $existentes[$data] ?? null;
                    $anteriorImportado = $atual !== null && !empty($atual['planejado_json']) ? (json_decode((string)$atual['planejado_json'], true) ?: []) : [];
                    $indiceModelo = (int)date('N', strtotime($data)) - 1;
                    $linhaModelo = $modelo['dias'][$indiceModelo] ?? [];
                    $novo = [];
                    $planejado = [];
                    $alertas = [];
                    $estados = [];
                    foreach ($slots as $slot) {
                        $p = trim((string)$dia['refeicoes'][$slot]['descricao']);
                        $planejado[$slot] = $p;
                        if (($dia['refeicoes'][$slot]['alertas'] ?? []) !== []) {
                            $alertas[$slot] = array_map(static fn(array $a): array => ['alimento' => (string)$a['alimento'], 'orientacao' => (string)$a['orientacao']], $dia['refeicoes'][$slot]['alertas']);
                        }
                        $c = trim((string)($atual[$slot] ?? ''));
                        if ($c === $p) {
                            $estado = 'identico';
                        } elseif ($c === '') {
                            $estado = 'criar';
                        } elseif ($c === trim((string)($anteriorImportado[$slot] ?? "\0")) || $c === trim((string)($linhaModelo[$slot] ?? "\0"))) {
                            $estado = 'atualizar';
                        } else {
                            $estado = $sobrescreverManuais ? 'sobrescrever' : 'conflito';
                        }
                        $estados[$slot] = ['estado' => $estado, 'atual' => $c, 'planejado' => $p];
                        $novo[$slot] = in_array($estado, ['criar', 'atualizar', 'sobrescrever', 'identico'], true) ? $p : $c;
                        $resumo[match ($estado) { 'criar' => 'criar', 'identico' => 'identicos', 'atualizar' => 'atualizar', 'sobrescrever' => 'sobrescritos', default => 'conflitos' }]++;
                    }
                    $resumo['linhas'][] = ['data' => $data, 'dia_semana' => (string)$dia['dia_semana'], 'slots' => $estados, 'alertas' => $alertas];
                    if ($aplicar && $semanaId > 0) {
                        $repositorio->salvarDia($semanaId, $data, $novo['fruta_manha'] ?: null, $novo['fruta_tarde'] ?: null, $novo['almoco'] ?: null, $novo['jantar'] ?: null,
                            self::slugsDoTexto($novo['almoco']), self::slugsDoTexto($novo['jantar']));
                        $repositorio->salvarPlanejamentoDia($semanaId, $data, $planejado, $alertas);
                        $repositorio->tocarSemana($semanaId);
                    }
                }
            }
            if ($aplicar) {
                $bd->commit();
            }
        } catch (\Throwable $e) {
            if ($aplicar && $bd->inTransaction()) {
                $bd->rollBack();
            }
            throw $e;
        }
        if ($aplicar && ($resumo['criar'] + $resumo['atualizar'] + $resumo['sobrescritos']) > 0) {
            $this->avisarCuidadoras(
                'Cardápio planejado até ' . data_br((string)$plano['fim'] . ' 00:00:00', 'd/m'),
                'Os pais carregaram o planejamento das refeições de ' . data_br((string)$plano['inicio'] . ' 00:00:00', 'd/m') . ' a '
                . data_br((string)$plano['fim'] . ' 00:00:00', 'd/m') . ': fruta da manhã, almoço, fruta da tarde e jantar. Veja o cardápio de cada dia no Meu Dia.',
                url('compras') . '?crianca=' . $criancaId
            );
        }
        return $resumo;
    }

    /** Alertas do dia que ainda fazem sentido para o texto atual (ex.: pais trocaram o ovo → alerta some). */
    public static function alertasDoDia(?array $dia): array
    {
        if ($dia === null || empty($dia['alertas_json'])) {
            return [];
        }
        $alertas = json_decode((string)$dia['alertas_json'], true) ?: [];
        $saida = [];
        foreach ($alertas as $slot => $lista) {
            $texto = mb_strtolower((string)($dia[$slot] ?? ''));
            foreach ((array)$lista as $a) {
                if ($texto !== '' && str_contains($texto, mb_strtolower((string)$a['alimento']))) {
                    $saida[$slot][] = $a;
                }
            }
        }
        return $saida;
    }

    // ── Cardápio ──────────────────────────────────────────────

    /**
     * Salva os 7 dias enviados pelos pais e devolve um resumo das mudanças
     * (para o aviso à cuidadora). @param array<string,array<string,string>> $dias data => campos
     */
    public function salvarCardapio(array $semana, array $dias): array
    {
        $repositorio = new RepositorioCardapio($this->familiaId);
        $antes = [];
        foreach ($repositorio->diasDaSemana((int)$semana['id']) as $dia) {
            $antes[(string)$dia['data']] = $dia;
        }
        $mudancas = [];
        foreach ($dias as $data => $campos) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data) !== 1 || $data < $semana['inicio'] || $data > $semana['fim']) {
                continue;
            }
            $limpar = static fn($v): ?string => trim((string)$v) !== '' ? mb_substr(trim((string)$v), 0, 255) : null;
            $fm = $limpar($campos['fruta_manha'] ?? '');
            $ft = $limpar($campos['fruta_tarde'] ?? '');
            $almoco = $limpar($campos['almoco'] ?? '');
            $jantar = $limpar($campos['jantar'] ?? '');
            $repositorio->salvarDia((int)$semana['id'], (string)$data, $fm, $ft, $almoco, $jantar,
                self::slugsDoTexto((string)$almoco), self::slugsDoTexto((string)$jantar));
            $anterior = $antes[$data] ?? null;
            if ($anterior === null || $anterior['fruta_manha'] !== $fm || $anterior['fruta_tarde'] !== $ft
                || $anterior['almoco'] !== $almoco || $anterior['jantar'] !== $jantar) {
                $mudancas[] = data_br($data . ' 00:00:00', 'd/m') . ': ' . implode(' · ', array_filter([
                    $fm !== null ? 'manhã ' . $fm : null, $ft !== null ? 'tarde ' . $ft : null,
                    $almoco !== null ? 'almoço ' . $almoco : null, $jantar !== null ? 'jantar ' . $jantar : null,
                ]));
            }
        }
        $repositorio->tocarSemana((int)$semana['id']);
        return $mudancas;
    }

    /** Cardápio do dia da criança (ou null se não há semana cobrindo a data). */
    public function cardapioDoDia(int $criancaId, string $data): ?array
    {
        try {
            return (new RepositorioCardapio($this->familiaId))->diaDaData($criancaId, $data);
        } catch (\Throwable) {
            return null; // tabela ainda não migrada
        }
    }

    // ── Compras ───────────────────────────────────────────────

    /** @return array{total:int, comprados:int, porcentagem:int, categorias:array<string,array{total:int,comprados:int,itens:array}>} */
    public static function progresso(array $itens): array
    {
        $categorias = [];
        $comprados = 0;
        foreach ($itens as $item) {
            $cat = (string)$item['categoria'];
            $categorias[$cat] ??= ['total' => 0, 'comprados' => 0, 'itens' => []];
            $categorias[$cat]['total']++;
            $categorias[$cat]['itens'][] = $item;
            if ((int)$item['comprado'] === 1) {
                $categorias[$cat]['comprados']++;
                $comprados++;
            }
        }
        $total = count($itens);
        return [
            'total' => $total,
            'comprados' => $comprados,
            'porcentagem' => $total > 0 ? (int)round($comprados * 100 / $total) : 0,
            'categorias' => $categorias,
        ];
    }

    /**
     * Importação SEGURA do HTML exportado pela lista original: lê o arquivo
     * como texto, extrai só o JSON de <script id="embedded-state"> e valida
     * contra os stable_ids da semana. Nada do HTML é executado ou renderizado.
     * @return array{erro:?string, marcar:string[], desconhecidos:int, atualizado_em:?string}
     */
    public static function lerEstadoDoHtml(string $conteudo, array $stableIdsConhecidos): array
    {
        if (strlen($conteudo) > self::TAMANHO_MAXIMO_HTML) {
            return ['erro' => 'Arquivo grande demais (limite de 2 MB).', 'marcar' => [], 'desconhecidos' => 0, 'atualizado_em' => null];
        }
        if (preg_match('/<script[^>]*id=["\']embedded-state["\'][^>]*>(.*?)<\/script>/is', $conteudo, $m) !== 1) {
            return ['erro' => 'Este arquivo não tem o estado salvo (#embedded-state). Abra a lista original, marque os itens e use "Salvar HTML com marcações" — e envie esse arquivo baixado.', 'marcar' => [], 'desconhecidos' => 0, 'atualizado_em' => null];
        }
        $estado = json_decode(trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')), true);
        if (!is_array($estado) || !isset($estado['checks']) || !is_array($estado['checks'])) {
            return ['erro' => 'O estado salvo no arquivo está em um formato inesperado.', 'marcar' => [], 'desconhecidos' => 0, 'atualizado_em' => null];
        }
        $marcar = [];
        $desconhecidos = 0;
        foreach ($estado['checks'] as $id => $valor) {
            if ($valor !== true) {
                continue;
            }
            $id = (string)$id;
            if (preg_match('/^[a-z0-9_-]{1,40}$/', $id) !== 1) {
                $desconhecidos++;
                continue;
            }
            if (in_array($id, $stableIdsConhecidos, true)) {
                $marcar[] = $id;
            } else {
                $desconhecidos++;
            }
        }
        $atualizadoEm = isset($estado['updatedAt']) && (int)$estado['updatedAt'] > 0
            ? date('Y-m-d H:i:s', (int)((int)$estado['updatedAt'] / 1000)) : null;
        if ($marcar === [] && $desconhecidos === 0) {
            return ['erro' => 'O arquivo foi lido, mas não tem nenhum item marcado. Marque os itens na lista original e use "Salvar HTML com marcações" antes de importar.', 'marcar' => [], 'desconhecidos' => 0, 'atualizado_em' => $atualizadoEm];
        }
        return ['erro' => null, 'marcar' => array_values(array_unique($marcar)), 'desconhecidos' => $desconhecidos, 'atualizado_em' => $atualizadoEm];
    }

    // ── Avisos à cuidadora ────────────────────────────────────

    /** Aviso no app + push + e-mail para as cuidadoras ativas da família. */
    public function avisarCuidadoras(string $titulo, string $mensagem, string $url): void
    {
        (new ServicoAvisos($this->familiaId))->avisarCuidadores('cardapio', $titulo, $mensagem, $url);
        try {
            $familiaId = $this->familiaId ?? Autenticacao::familiaId();
            $email = new ServicoEmail();
            foreach ((new RepositorioUsuariosFamilia($familiaId))->listar() as $usuario) {
                if ($usuario['papel'] !== 'cuidador' || (int)$usuario['ativo'] !== 1 || (string)$usuario['email'] === '') {
                    continue;
                }
                $nome = mb_convert_case(trim(explode(' ', (string)$usuario['nome'])[0]), MB_CASE_TITLE, 'UTF-8');
                $email->enviarHtml(
                    (string)$usuario['email'],
                    $titulo . ' — Diário do Bebê',
                    '<h2 style="margin:0 0 12px; font-size:19px;">' . e($titulo) . '</h2>'
                    . '<p style="margin:0 0 12px;">Olá, ' . e($nome) . '!</p>'
                    . '<p style="margin:0 0 12px; white-space:pre-line;">' . e($mensagem) . '</p>'
                    . ServicoEmail::botao('Ver o cardápio e a lista de compras', url_absoluta('compras')),
                    "Olá, {$nome}!\n\n{$titulo}\n\n{$mensagem}\n\nVeja em: " . url_absoluta('compras'),
                    'cardapio',
                    null,
                    $familiaId
                );
            }
        } catch (\Throwable $excecao) {
            error_log('Falha ao avisar cuidadoras sobre o cardápio: ' . $excecao->getMessage());
        }
    }

    // ── Texto do cardápio → slugs do catálogo (pré-preencher o registro) ──

    /** @return string[] slugs do catálogo encontrados no texto livre do cardápio */
    public static function slugsDoTexto(string $texto): array
    {
        if (trim($texto) === '') {
            return [];
        }
        $apelidos = [
            'frango' => 'frango', 'peito de frango' => 'frango', 'carne moida' => 'carne-bovina', 'carne' => 'carne-bovina',
            'carne bovina' => 'carne-bovina', 'patinho' => 'carne-bovina', 'musculo' => 'carne-bovina',
            'feijao carioca' => 'feijao', 'feijao-carioca' => 'feijao', 'feijao preto' => 'feijao', 'feijao-preto' => 'feijao', 'feijao' => 'feijao',
            'mandioquinha' => 'batata-baroa', 'batata baroa' => 'batata-baroa', 'batata-baroa' => 'batata-baroa',
            'batata doce' => 'batata-doce', 'batata-doce' => 'batata-doce', 'aipim' => 'mandioca', 'macaxeira' => 'mandioca',
            'maca cozida' => 'maca', 'maca' => 'maca', 'mamao' => 'mamao', 'papaia' => 'mamao', 'brocolis' => 'brocolis',
            'ovo' => 'ovo', 'ovo cozido' => 'ovo', 'peixe' => 'peixe', 'tilapia' => 'peixe', 'figado' => 'figado',
            'grao de bico' => 'grao-de-bico', 'grao-de-bico' => 'grao-de-bico', 'ervilha' => 'ervilha', 'lentilha' => 'lentilha',
            'abobora' => 'abobora', 'moranga' => 'abobora', 'inhame' => 'inhame', 'cara' => 'cara', 'milho' => 'milho',
            'fuba' => 'milho', 'polenta' => 'milho', 'macarrao' => 'macarrao', 'aveia' => 'aveia', 'quinoa' => 'quinoa',
            'iogurte' => 'iogurte-natural', 'queijo' => 'queijo', 'azeite' => 'azeite',
        ];
        $normalizar = static function (string $t): string {
            $t = mb_strtolower(trim($t));
            $sem = @iconv('UTF-8', 'ASCII//TRANSLIT', $t);
            return trim(preg_replace('/\s+/', ' ', $sem !== false ? $sem : $t) ?? '');
        };
        $itens = ServicoAlimentacao::itens();
        $porNome = [];
        foreach ($itens as $slug => $item) {
            if (!empty($item['evitar'])) {
                continue;
            }
            // Primeiro termo do nome ("Batata-doce", "Abóbora / moranga" → "abobora")
            $primeiro = $normalizar((string)preg_split('/[\/(,]/', (string)$item['nome'])[0]);
            $porNome[$primeiro] = $slug;
        }
        $slugs = [];
        foreach (preg_split('/,|;| e | com | \+ /u', $texto) ?: [] as $pedaco) {
            $chave = $normalizar($pedaco);
            $chave = preg_replace('/\b(cozid[oa]s?|amassad[oa]s?|refogad[oa]s?|desfiad[oa]s?|moid[oa]s?)\b/', '', $chave) ?? $chave;
            $chave = trim($chave);
            if ($chave === '') {
                continue;
            }
            $slug = $apelidos[$chave] ?? $porNome[$chave] ?? null;
            if ($slug === null) {
                foreach ($apelidos as $apelido => $destino) {
                    if (str_starts_with($chave, $apelido) || str_starts_with($apelido, $chave)) {
                        $slug = $destino;
                        break;
                    }
                }
            }
            if ($slug === null) {
                foreach ($porNome as $nome => $destino) {
                    if (str_starts_with($chave, $nome) || str_starts_with($nome, $chave)) {
                        $slug = $destino;
                        break;
                    }
                }
            }
            if ($slug !== null && isset($itens[$slug]) && !in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }
        return $slugs;
    }
}
