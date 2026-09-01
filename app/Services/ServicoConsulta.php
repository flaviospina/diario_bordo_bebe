<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioConsultasMedicas;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioMedicoes;
use App\Repositories\RepositorioProfissionais;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioSistema;
use App\Repositories\RepositorioVacinas;

/**
 * Ficha para a consulta do pediatra (Alteração 01).
 *
 * O link é de USO ÚNICO, com validade de 48 h e sem login: por isso tudo que
 * chega por ele entra como PENDENTE até a confirmação dos pais — a trava
 * contra erro de digitação e uso indevido. Sem confirmação em 7 dias, o
 * sistema confirma sozinho e registra a confirmação automática na auditoria.
 */
final class ServicoConsulta extends RepositorioSistema
{
    private const LIMITE_LINKS_ATIVOS = 3;
    private const DIAS_CONFIRMACAO_AUTOMATICA = 7;

    // ── Geração e gestão do link (responsável autenticado) ────

    /** @return array{erro:?string, codigo:?string} */
    public function gerarLink(array $crianca): array
    {
        $consultas = new RepositorioConsultasMedicas();
        if (count($consultas->convitesAtivos((int)$crianca['id'])) >= self::LIMITE_LINKS_ATIVOS) {
            return ['erro' => 'Esta criança já tem ' . self::LIMITE_LINKS_ATIVOS
                . ' links ativos. Revogue um antes de gerar outro.', 'codigo' => null];
        }
        $codigo = $consultas->criarConvite((int)$crianca['id'], Autenticacao::id());
        (new RepositorioLogAcessos())->registrar(
            Autenticacao::familiaId(), Autenticacao::id(),
            'consulta_link_gerado', 'convites_consulta', null, null
        );
        return ['erro' => null, 'codigo' => $codigo];
    }

    // ── Acesso público pelo código ────────────────────────────

    /**
     * Resolve o código SEM sessão (o pediatra não tem login).
     * @return array{erro:?string, convite:?array, crianca:?array}
     */
    public function abrirPorCodigo(string $codigo, bool $marcarAbertura = false): array
    {
        if (preg_match('/^[0-9a-z]{12}$/', $codigo) !== 1) {
            return ['erro' => 'inexistente', 'convite' => null, 'crianca' => null];
        }
        $convite = $this->buscarUm(
            'SELECT cc.*, c.nome AS crianca_nome, c.slug AS crianca_slug,
                    (cc.expira_em <= NOW()) AS ja_expirado
               FROM convites_consulta cc
               JOIN criancas c ON c.id = cc.crianca_id
              WHERE cc.codigo_publico = :codigo LIMIT 1',
            ['codigo' => $codigo]
        );
        if ($convite === null) {
            return ['erro' => 'inexistente', 'convite' => null, 'crianca' => null];
        }
        if ($convite['status'] === 'revogado') {
            return ['erro' => 'revogado', 'convite' => null, 'crianca' => null];
        }
        if ($convite['status'] === 'usado') {
            return ['erro' => 'usado', 'convite' => null, 'crianca' => null];
        }
        // Expiração comparada no relógio do BANCO (o mesmo do NOW() da criação):
        // relógios diferentes entre PHP e MySQL não podem reabrir um link vencido.
        if ($convite['status'] !== 'aberto' || (int)$convite['ja_expirado'] === 1) {
            return ['erro' => 'expirado', 'convite' => null, 'crianca' => null];
        }

        $crianca = $this->buscarUm(
            'SELECT * FROM criancas WHERE id = :id LIMIT 1',
            ['id' => (int)$convite['crianca_id']]
        );

        // Primeira abertura: registra e avisa os pais (evento consulta_link_aberto)
        if ($marcarAbertura && $convite['aberto_em'] === null) {
            $this->executar(
                'UPDATE convites_consulta SET aberto_em = NOW() WHERE id = :id',
                ['id' => (int)$convite['id']]
            );
            (new ServicoNotificacoes())->enfileirarParaResponsaveis(
                (int)$convite['familia_id'],
                'consulta_link_aberto',
                'Ficha da consulta aberta — Diário do Bebê',
                'A ficha de ' . $convite['crianca_nome'] . ' foi aberta pelo link da consulta.',
                ['codigo' => $codigo]
            );
        }
        return ['erro' => null, 'convite' => $convite, 'crianca' => $crianca];
    }

    /** Resumo dos últimos 30 dias de rotina para a ficha do pediatra. */
    public function resumoRotina(int $familiaId, int $criancaId): array
    {
        $de = date('Y-m-d', strtotime('-29 days'));
        $registros = (new RepositorioRegistros($familiaId))
            ->linhaDoTempo($criancaId, null, $de, hoje(), 5000);

        $dias = [];
        $mamadas = 0;
        $fraldas = 0;
        $minutosSono = 0;
        $sintomas = [];
        foreach ($registros as $registro) {
            $dias[substr((string)$registro['inicio'], 0, 10)] = true;
            switch ($registro['categoria_slug']) {
                case 'amamentacao':
                case 'mamadeira':
                    $mamadas++;
                    break;
                case 'fralda':
                    $fraldas++;
                    break;
                case 'soneca':
                case 'sono-noturno':
                    if ($registro['fim'] !== null) {
                        $minutosSono += max(0, (int)((strtotime((string)$registro['fim']) - strtotime((string)$registro['inicio'])) / 60));
                    }
                    break;
                case 'sintoma':
                    $dados = json_decode((string)($registro['dados'] ?? 'null'), true) ?: [];
                    $tipo = (string)($dados['tipo'] ?? 'outro');
                    $sintomas[$tipo] = ($sintomas[$tipo] ?? 0) + 1;
                    break;
            }
        }
        $diasComDado = max(1, count($dias));
        $intercorrencias = array_values(array_filter(
            (new RepositorioIntercorrencias($familiaId))->listar(50),
            static fn(array $i): bool => (int)$i['crianca_id'] === $criancaId
                && strtotime((string)$i['ocorrido_em']) >= strtotime($de)
        ));

        return [
            'dias_com_registro' => count($dias),
            'sono_medio_min' => (int)round($minutosSono / $diasComDado),
            'mamadas_dia' => round($mamadas / $diasComDado, 1),
            'fraldas_dia' => round($fraldas / $diasComDado, 1),
            'sintomas' => $sintomas,
            'intercorrencias' => $intercorrencias,
        ];
    }

    /**
     * Recebe o envio do pediatra e QUEIMA o link.
     * @return array{erro:?string}
     */
    public function registrarEnvio(string $codigo, array $entrada, string $ip, string $userAgent): array
    {
        $abertura = $this->abrirPorCodigo($codigo);
        if ($abertura['erro'] !== null) {
            return ['erro' => 'Link inválido, usado ou expirado.'];
        }
        $convite = $abertura['convite'];
        $crianca = $abertura['crianca'];
        $familiaId = (int)$convite['familia_id'];

        $nomeProfissional = trim((string)($entrada['profissional_nome'] ?? ''));
        if ($nomeProfissional === '') {
            return ['erro' => 'Informe o seu nome.'];
        }
        $peso = self::numeroOuNulo($entrada['peso_kg'] ?? null);
        $altura = self::numeroOuNulo($entrada['altura_cm'] ?? null);
        $pc = self::numeroOuNulo($entrada['pc_cm'] ?? null);
        $vacinasTexto = trim((string)($entrada['vacinas'] ?? ''));
        $conduta = trim((string)($entrada['conduta'] ?? ''));
        if ($peso === null && $altura === null && $pc === null && $vacinasTexto === '' && $conduta === '') {
            return ['erro' => 'Preencha ao menos um valor (medida, vacina ou conduta).'];
        }
        if (($peso !== null && ($peso < 0.3 || $peso > 60))
            || ($altura !== null && ($altura < 20 || $altura > 160))
            || ($pc !== null && ($pc < 20 || $pc > 70))) {
            return ['erro' => 'Confira os valores: peso em kg, altura e perímetro cefálico em cm.'];
        }

        $profissionalId = (new RepositorioProfissionais())->localizarOuCriar(
            $nomeProfissional,
            mb_strtoupper(trim((string)($entrada['conselho_sigla'] ?? ''))),
            trim((string)($entrada['conselho_numero'] ?? '')),
            mb_strtoupper(trim((string)($entrada['conselho_uf'] ?? '')))
        );

        $dataMedicao = self::dataOuHoje($entrada['medido_em'] ?? null);
        $medicaoId = null;
        if ($peso !== null || $altura !== null || $pc !== null) {
            $percentis = $this->calcularPercentis($crianca, $dataMedicao, $peso, $altura, $pc);
            $medicaoId = (new RepositorioMedicoes($familiaId))->criar([
                'crianca_id' => (int)$crianca['id'],
                'medido_em' => $dataMedicao,
                'peso_g' => $peso !== null ? (int)round($peso * 1000) : null,
                'altura_mm' => $altura !== null ? (int)round($altura * 10) : null,
                'perimetro_cefalico_mm' => $pc !== null ? (int)round($pc * 10) : null,
                'origem' => 'pediatra',
                'profissional_id' => $profissionalId,
                'observacao' => null,
                'status' => 'pendente', // trava: os pais confirmam (ou 7 dias)
            ] + $percentis);
        }

        // Vacinas em texto livre, uma por linha ("Imunizante — dose")
        if ($vacinasTexto !== '') {
            $vacinas = new RepositorioVacinas($familiaId);
            foreach (array_slice(preg_split('/\r?\n/', $vacinasTexto) ?: [], 0, 10) as $linha) {
                $linha = trim($linha);
                if ($linha === '') {
                    continue;
                }
                $partes = preg_split('/\s+[—\-]\s+/u', $linha, 2) ?: [$linha];
                $vacinas->criar([
                    'crianca_id' => (int)$crianca['id'],
                    'imunizante' => mb_substr($partes[0], 0, 120),
                    'dose' => mb_substr($partes[1] ?? 'dose', 0, 40),
                    'aplicada_em' => $dataMedicao,
                    'origem' => 'pediatra',
                    'profissional_id' => $profissionalId,
                    'status' => 'aplicada',
                ]);
            }
        }

        (new RepositorioConsultasMedicas($familiaId))->criarConsulta([
            'crianca_id' => (int)$crianca['id'],
            'profissional_id' => $profissionalId,
            'realizada_em' => $dataMedicao,
            'motivo' => trim((string)($entrada['motivo'] ?? '')) ?: null,
            'conduta' => $conduta !== '' ? $conduta : null,
            'retorno_em' => self::dataOuNulo($entrada['retorno_em'] ?? null),
            'origem' => 'pediatra',
        ]);

        // Queima o link: uso único, com rastro completo
        $this->executar(
            "UPDATE convites_consulta
                SET status = 'usado', usado_em = NOW(), profissional_id = :profissional,
                    ip_uso = :ip, user_agent_uso = :ua
              WHERE id = :id AND status = 'aberto'",
            ['id' => (int)$convite['id'], 'profissional' => $profissionalId,
             'ip' => $ip, 'ua' => mb_substr($userAgent, 0, 255)]
        );

        $log = new RepositorioLogAcessos();
        $log->registrar($familiaId, null, 'consulta_link_usado', 'convites_consulta', (int)$convite['id'], $ip, $userAgent);
        if ($medicaoId !== null) {
            $log->registrar($familiaId, null, 'medicao_pendente_criada', 'medicoes', $medicaoId, $ip);
        }

        $notificacoes = new ServicoNotificacoes();
        $notificacoes->enfileirarParaResponsaveis(
            $familiaId,
            $medicaoId !== null ? 'medicao_pendente' : 'consulta_link_usado',
            'Consulta registrada — Diário do Bebê',
            $nomeProfissional . ' registrou a consulta de ' . $crianca['nome']
            . ($peso !== null ? ' (' . number_format($peso, 3, ',', '.') . ' kg)' : '')
            . ($medicaoId !== null ? '. Confirme a medição no app.' : '.'),
            ['crianca' => $crianca['slug'], 'medicao_id' => $medicaoId],
            ['whatsapp', 'email'],
            true
        );
        return ['erro' => null];
    }

    // ── Medição manual pelo responsável e confirmação ─────────

    /** @return array{erro:?string} */
    public function medicaoManual(array $crianca, array $entrada): array
    {
        $peso = self::numeroOuNulo($entrada['peso_kg'] ?? null);
        $altura = self::numeroOuNulo($entrada['altura_cm'] ?? null);
        $pc = self::numeroOuNulo($entrada['pc_cm'] ?? null);
        if ($peso === null && $altura === null && $pc === null) {
            return ['erro' => 'Informe ao menos uma medida.'];
        }
        if (($peso !== null && ($peso < 0.3 || $peso > 60))
            || ($altura !== null && ($altura < 20 || $altura > 160))
            || ($pc !== null && ($pc < 20 || $pc > 70))) {
            return ['erro' => 'Confira os valores: peso em kg, altura e perímetro cefálico em cm.'];
        }
        $dataMedicao = self::dataOuHoje($entrada['medido_em'] ?? null);
        $percentis = $this->calcularPercentis($crianca, $dataMedicao, $peso, $altura, $pc);
        (new RepositorioMedicoes())->criar([
            'crianca_id' => (int)$crianca['id'],
            'medido_em' => $dataMedicao,
            'peso_g' => $peso !== null ? (int)round($peso * 1000) : null,
            'altura_mm' => $altura !== null ? (int)round($altura * 10) : null,
            'perimetro_cefalico_mm' => $pc !== null ? (int)round($pc * 10) : null,
            'origem' => 'pais',
            'registrado_por_usuario_id' => Autenticacao::id(),
            'profissional_nome_livre' => trim((string)($entrada['profissional_nome'] ?? '')) ?: null,
            'observacao' => trim((string)($entrada['observacao'] ?? '')) ?: null,
            'status' => 'confirmada',
        ] + $percentis);
        (new RepositorioLogAcessos())->registrar(
            Autenticacao::familiaId(), Autenticacao::id(), 'medicao_criada', 'medicoes', null, null
        );
        return ['erro' => null];
    }

    public function confirmarMedicao(int $medicaoId): ?string
    {
        $medicoes = new RepositorioMedicoes();
        $medicao = $medicoes->buscar($medicaoId);
        if ($medicao === null || $medicao['status'] !== 'pendente') {
            return 'Medição inexistente ou já confirmada.';
        }
        $medicoes->confirmar($medicaoId, Autenticacao::id());
        (new RepositorioLogAcessos())->registrar(
            Autenticacao::familiaId(), Autenticacao::id(), 'medicao_confirmada', 'medicoes', $medicaoId, null
        );
        return null;
    }

    /** Tarefa agendada: confirma sozinho após 7 dias, com rastro na auditoria. */
    public function confirmarAutomaticas(): int
    {
        $pendentes = $this->executar(
            "SELECT id, familia_id FROM medicoes
              WHERE status = 'pendente'
                AND criado_em < DATE_SUB(NOW(), INTERVAL :dias DAY)",
            ['dias' => self::DIAS_CONFIRMACAO_AUTOMATICA]
        )->fetchAll();

        $log = new RepositorioLogAcessos();
        foreach ($pendentes as $medicao) {
            $this->executar(
                "UPDATE medicoes SET status = 'confirmada', confirmado_em = NOW()
                  WHERE id = :id AND status = 'pendente'",
                ['id' => (int)$medicao['id']]
            );
            $log->registrar((int)$medicao['familia_id'], null, 'medicao_confirmada_automatica', 'medicoes', (int)$medicao['id'], null);
        }
        return count($pendentes);
    }

    // ── Auxiliares ────────────────────────────────────────────

    /** Percentis/escores-z congelados no momento da gravação. */
    private function calcularPercentis(array $crianca, string $dataMedicao, ?float $peso, ?float $altura, ?float $pc): array
    {
        $saida = [];
        $sexo = (string)($crianca['sexo'] ?? '');
        $nascimento = (string)($crianca['data_nascimento'] ?? '');
        if ($nascimento === '' || !in_array($sexo, ['masculino', 'feminino'], true)) {
            return $saida; // sem nascimento/sexo não há referência — grava sem percentil
        }
        $crescimento = new ServicoCrescimento();
        $idade = ServicoCrescimento::idadeEmMeses($nascimento, $dataMedicao);
        foreach ([['peso', $peso, 'peso'], ['altura', $altura, 'altura'], ['pc', $pc, 'pc']] as [$tipo, $valor, $sufixo]) {
            if ($valor === null) {
                continue;
            }
            $resultado = $crescimento->avaliar($tipo, $sexo, $idade, $valor);
            if ($resultado !== null) {
                $saida['percentil_' . $sufixo] = $resultado['percentil'];
                $saida['escore_z_' . $sufixo] = $resultado['z'];
            }
        }
        return $saida;
    }

    private static function numeroOuNulo(mixed $valor): ?float
    {
        if (!is_string($valor) && !is_numeric($valor)) {
            return null;
        }
        $texto = str_replace(',', '.', trim((string)$valor));
        return is_numeric($texto) ? (float)$texto : null;
    }

    private static function dataOuHoje(mixed $valor): string
    {
        return self::dataOuNulo($valor) ?? hoje();
    }

    private static function dataOuNulo(mixed $valor): ?string
    {
        return is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : null;
    }
}
