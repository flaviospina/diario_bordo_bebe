<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Autenticacao;
use App\Repositories\RepositorioIntercorrencias;
use App\Repositories\RepositorioLogAcessos;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioSolicitacoes;
use App\Repositories\RepositorioSuprimentos;
use App\Repositories\RepositorioTurnos;
use App\Repositories\RepositorioVersoes;

/**
 * Regras de negócio dos registros do diário:
 *  - validação dirigida pelo schema_campos JSON da categoria;
 *  - efeitos colaterais por grupo (intercorrência, suprimento, turno);
 *  - turno automático do cuidador no primeiro registro do dia (regra 8.8);
 *  - versionamento imutável de toda alteração (regra 8.1);
 *  - permissão de edição conforme a configuração da família (regra 8.2).
 */
final class ServicoRegistros
{
    public function __construct(
        private readonly RepositorioRegistros $registros = new RepositorioRegistros(),
        private readonly RepositorioVersoes $versoes = new RepositorioVersoes(),
        private readonly ServicoConfiguracoes $config = new ServicoConfiguracoes(),
        private readonly RepositorioLogAcessos $log = new RepositorioLogAcessos(),
    ) {
    }

    // ── Validação dirigida pelo schema ────────────────────────

    /**
     * Valida e normaliza os campos dinâmicos de uma categoria.
     * @param array<string,mixed> $categoria linha da tabela categorias
     * @param array<string,mixed> $entrada   valores brutos (POST ou JSON)
     * @param bool $exigirObrigatorios       false quando status = nao_feito
     * @return array{dados:array<string,mixed>, erros:string[]}
     */
    public function validarCampos(array $categoria, array $entrada, bool $exigirObrigatorios = true): array
    {
        $schema = json_decode((string)$categoria['schema_campos'], true) ?: [];
        $dados = [];
        $erros = [];

        foreach (($schema['campos'] ?? []) as $campo) {
            $nome = (string)$campo['nome'];
            $rotulo = (string)($campo['rotulo'] ?? $nome);
            $bruto = $entrada[$nome] ?? null;
            $bruto = is_string($bruto) ? trim($bruto) : $bruto;

            if ($bruto === null || $bruto === '') {
                if (!empty($campo['obrigatorio']) && $exigirObrigatorios) {
                    $erros[] = "Preencha o campo \"{$rotulo}\".";
                }
                continue;
            }

            switch ($campo['tipo']) {
                case 'opcoes':
                    $validos = array_column($campo['opcoes'] ?? [], 'valor');
                    if (!in_array($bruto, $validos, true)) {
                        $erros[] = "Valor inválido em \"{$rotulo}\".";
                        break;
                    }
                    $dados[$nome] = $bruto;
                    break;
                case 'numero':
                    if (!is_numeric($bruto)) {
                        $erros[] = "\"{$rotulo}\" precisa ser um número.";
                        break;
                    }
                    $numero = (float)$bruto;
                    $minimo = $campo['minimo'] ?? null;
                    $maximo = $campo['maximo'] ?? null;
                    if (($minimo !== null && $numero < $minimo) || ($maximo !== null && $numero > $maximo)) {
                        $erros[] = "\"{$rotulo}\" fora do intervalo permitido.";
                        break;
                    }
                    $dados[$nome] = $numero == (int)$numero ? (int)$numero : $numero;
                    break;
                case 'duracao_minutos':
                    $minutos = (int)$bruto;
                    if ($minutos < 0 || $minutos > 1440) {
                        $erros[] = "\"{$rotulo}\" precisa estar entre 0 e 1440 minutos.";
                        break;
                    }
                    $dados[$nome] = $minutos;
                    break;
                case 'escala':
                    $nivel = (int)$bruto;
                    $maximo = (int)($campo['maximo'] ?? 5);
                    if ($nivel < 1 || $nivel > $maximo) {
                        $erros[] = "\"{$rotulo}\" precisa estar entre 1 e {$maximo}.";
                        break;
                    }
                    $dados[$nome] = $nivel;
                    break;
                case 'texto_longo':
                    $dados[$nome] = mb_substr((string)$bruto, 0, 2000);
                    break;
                default: // texto
                    $dados[$nome] = mb_substr((string)$bruto, 0, 255);
            }
        }
        return ['dados' => $dados, 'erros' => $erros];
    }

    // ── Criação ───────────────────────────────────────────────

    /**
     * Cria um registro com todos os efeitos colaterais.
     * @param array<string,mixed> $categoria
     * @param array<string,mixed> $registro  campos já validados
     * @return array{registro:array<string,mixed>, duplicado:bool, intercorrencia:?array}
     */
    public function criar(array $categoria, array $registro, string $ip): array
    {
        $registro['categoria_id'] = (int)$categoria['id'];
        $registro['usuario_id'] = Autenticacao::id();

        $resultado = $this->registros->criar($registro);
        if ($resultado['duplicado']) {
            return ['registro' => $resultado['registro'], 'duplicado' => true, 'intercorrencia' => null];
        }
        $criado = $resultado['registro'];

        // Turno automático: primeiro registro do dia do cuidador abre o turno
        $this->abrirTurnoAutomatico((string)$criado['inicio']);

        $intercorrencia = $this->executarEfeitosColaterais($categoria, $criado);

        $this->log->registrar(
            Autenticacao::familiaId(),
            Autenticacao::id(),
            'registro_criado',
            'registros',
            (int)$criado['id'],
            $ip
        );
        return ['registro' => $criado, 'duplicado' => false, 'intercorrencia' => $intercorrencia];
    }

    private function abrirTurnoAutomatico(string $inicioRegistro): void
    {
        if (Autenticacao::papel() !== 'cuidador') {
            return;
        }
        $turnos = new RepositorioTurnos();
        $dia = substr($inicioRegistro, 0, 10);
        if ($dia === hoje() && !$turnos->temTurnoNoDia(Autenticacao::id(), $dia)) {
            $turnos->abrir(Autenticacao::id(), agora(), false);
        }
    }

    /** @return ?array{codigo:string, gravidade:string} dados da intercorrência criada, se houver */
    private function executarEfeitosColaterais(array $categoria, array $registro): ?array
    {
        $dados = json_decode((string)($registro['dados'] ?? 'null'), true) ?: [];

        // Intercorrência: linha própria com fluxo de ciência dos pais
        if ($categoria['grupo'] === 'intercorrencia') {
            $gravidade = (string)($dados['gravidade'] ?? 'leve');
            $codigo = (new RepositorioIntercorrencias())->criar(
                (int)$registro['crianca_id'],
                Autenticacao::id(),
                (string)$registro['inicio'],
                $gravidade,
                (string)$categoria['nome'] . (($registro['observacao'] ?? '') !== '' ? ' — ' . $registro['observacao'] : ''),
                isset($dados['acao_tomada']) ? (string)$dados['acao_tomada'] : null
            );
            return ['codigo' => $codigo, 'gravidade' => $gravidade];
        }

        // Pedido de suprimento vira item na lista de suprimentos
        if ($categoria['slug'] === 'pedido-suprimento' && !empty($dados['item'])) {
            (new RepositorioSuprimentos())->criar(
                (string)$dados['item'],
                in_array($dados['nivel'] ?? '', ['baixo', 'acabou'], true) ? (string)$dados['nivel'] : 'baixo',
                Autenticacao::id()
            );
        }

        // Categorias de turno controlam o expediente explicitamente
        if ($categoria['grupo'] === 'turno') {
            $turnos = new RepositorioTurnos();
            $dia = substr((string)$registro['inicio'], 0, 10);
            $aberto = $turnos->turnoAbertoDoDia(Autenticacao::id(), $dia);
            if ($categoria['slug'] === 'turno-entrada' && $aberto === null) {
                $turnos->abrir(Autenticacao::id(), (string)$registro['inicio'], false);
            } elseif ($categoria['slug'] === 'turno-saida' && $aberto !== null) {
                $turnos->fechar((int)$aberto['id'], (string)$registro['inicio']);
            }
        }
        return null;
    }

    // ── Permissão de edição (regra 8.2) ───────────────────────

    /**
     * O que o usuário atual pode fazer com o registro: 'editar' | 'solicitar' | 'nada'.
     */
    public function permissaoEdicao(array $registro): string
    {
        $papel = Autenticacao::papel();
        if (in_array($papel, ['responsavel', 'admin_familia'], true)) {
            return 'editar'; // quem aprova edita direto (a alteração fica versionada)
        }
        if ($papel !== 'cuidador') {
            return 'nada';
        }

        // Dias anteriores: bloqueado por padrão — vira solicitação (item 6 da seção 4)
        $diaDoRegistro = substr((string)$registro['inicio'], 0, 10);
        if ($diaDoRegistro < hoje() && !$this->config->obter('edicao_dias_anteriores')) {
            return 'solicitar';
        }

        $regra = $this->config->obter('regra_edicao');
        return match ((string)($regra['tipo'] ?? 'mesmo_dia')) {
            'livre' => 'editar',
            'mesmo_dia' => substr((string)$registro['criado_em'], 0, 10) === hoje() ? 'editar' : 'solicitar',
            'janela_minutos' => (strtotime((string)$registro['criado_em']) + 60 * (int)($regra['janela_minutos'] ?? 60)) >= time()
                ? 'editar' : 'solicitar',
            'somente_com_aprovacao' => 'solicitar',
            default => 'solicitar',
        };
    }

    // ── Edição e exclusão (sempre versionadas) ────────────────

    /** @return string[] campos que compõem o snapshot de auditoria */
    public static function snapshot(array $registro): array
    {
        return [
            'inicio'        => (string)$registro['inicio'],
            'fim'           => $registro['fim'],
            'dados'         => is_string($registro['dados'] ?? null)
                ? json_decode($registro['dados'], true)
                : ($registro['dados'] ?? null),
            'observacao'    => $registro['observacao'],
            'status'        => (string)$registro['status'],
            'justificativa' => $registro['justificativa'],
            'excluido'      => $registro['excluido_em'] !== null,
        ];
    }

    /** @param array<string,mixed> $novosCampos */
    public function editar(array $registro, array $novosCampos, ?string $motivo, string $ip): void
    {
        $anterior = self::snapshot($registro);
        $this->registros->atualizar((int)$registro['id'], $novosCampos);
        $atualizado = $this->registros->buscarPorCodigo((string)$registro['codigo_publico']);
        $this->versoes->gravar(
            (int)$registro['id'],
            Autenticacao::id(),
            $anterior,
            self::snapshot($atualizado ?? $registro),
            $motivo,
            $ip
        );
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'registro_editado', 'registros', (int)$registro['id'], $ip);
    }

    public function excluir(array $registro, string $motivo, string $ip): void
    {
        $anterior = self::snapshot($registro);
        $this->registros->excluirLogicamente((int)$registro['id'], Autenticacao::id(), $motivo);
        $novo = $anterior;
        $novo['excluido'] = true;
        $this->versoes->gravar((int)$registro['id'], Autenticacao::id(), $anterior, $novo, 'Exclusão: ' . $motivo, $ip);
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'registro_excluido', 'registros', (int)$registro['id'], $ip);
    }

    // ── Solicitações de alteração ─────────────────────────────

    /** @param array<string,mixed> $payload campos propostos (ou {excluir:true}) */
    public function solicitarAlteracao(array $registro, string $motivo, array $payload, string $tipo = 'edicao'): string
    {
        $codigo = (new RepositorioSolicitacoes())->criar(
            (int)$registro['id'],
            Autenticacao::id(),
            $tipo,
            $motivo,
            $payload
        );
        $this->log->registrar(Autenticacao::familiaId(), Autenticacao::id(), 'solicitacao_criada', 'solicitacoes_edicao', null, null);
        return $codigo;
    }

    /** Aplica o payload de uma solicitação aprovada (edição ou exclusão). */
    public function aplicarSolicitacaoAprovada(array $solicitacao, string $ip): void
    {
        $registro = $this->registros->buscarPorCodigo((string)$solicitacao['registro_codigo']);
        if ($registro === null) {
            return;
        }
        $payload = json_decode((string)$solicitacao['payload_proposto'], true) ?: [];
        $motivo = 'Solicitação ' . $solicitacao['codigo_publico'] . ' aprovada';

        if ($solicitacao['tipo'] === 'exclusao' || !empty($payload['excluir'])) {
            $this->excluir($registro, $motivo . ': ' . $solicitacao['motivo'], $ip);
            return;
        }
        $this->editar($registro, [
            'inicio'        => (string)($payload['inicio'] ?? $registro['inicio']),
            'fim'           => $payload['fim'] ?? $registro['fim'],
            'dados'         => $payload['dados'] ?? json_decode((string)($registro['dados'] ?? 'null'), true),
            'observacao'    => $payload['observacao'] ?? $registro['observacao'],
            'status'        => (string)($payload['status'] ?? $registro['status']),
            'justificativa' => $payload['justificativa'] ?? $registro['justificativa'],
        ], $motivo, $ip);
    }
}
