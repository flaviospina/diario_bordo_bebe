<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioConfiguracoes;
use App\Repositories\RepositorioCriancas;
use App\Repositories\RepositorioUsuariosFamilia;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoUsuariosFamilia;

final class ConfiguracaoController
{
    // ── Hub ───────────────────────────────────────────────────
    public function index(Requisicao $requisicao): void
    {
        Visao::exibir('config/index', [
            'titulo' => 'Configurações',
            'totalUsuarios' => count((new RepositorioUsuariosFamilia())->listar()),
            'totalCriancas' => count((new RepositorioCriancas())->listar(false)),
            'ehAdmin' => Autenticacao::temPapel('admin_familia'),
        ]);
    }

    // ── Configurações da família (seção 4) ────────────────────
    public function familia(Requisicao $requisicao): void
    {
        $servico = new ServicoConfiguracoes();
        Visao::exibir('config/familia', [
            'titulo' => 'Configurações da família',
            'config' => $servico->todas(),
            'historico' => (new RepositorioConfiguracoes())->historico(20),
        ]);
    }

    public function salvarFamilia(Requisicao $requisicao): void
    {
        $servico = new ServicoConfiguracoes();
        $usuarioId = Autenticacao::id();

        $hora = static function (?string $valor, string $padrao): string {
            return is_string($valor) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) === 1 ? $valor : $padrao;
        };

        // 1. Janela do dia (variação semanal simplificada: mesma janela todos os dias
        // quando desativada; por_dia é preenchido pela tela de roteiro no futuro)
        $janelaAtual = $servico->obter('janela_dia');
        $servico->salvar('janela_dia', [
            'inicio' => $hora($requisicao->post('janela_inicio'), '07:00'),
            'fim' => $hora($requisicao->post('janela_fim'), '19:00'),
            'variacao_semana' => $requisicao->post('variacao_semana') === '1',
            'por_dia' => $janelaAtual['por_dia'] ?? [],
        ], $usuarioId);

        // 2. Granularidade
        $granularidade = $requisicao->post('granularidade', 'flexivel');
        $servico->salvar(
            'granularidade',
            in_array($granularidade, ['flexivel', '15', '30', '60'], true)
                ? ($granularidade === 'flexivel' ? 'flexivel' : (int)$granularidade)
                : 'flexivel',
            $usuarioId
        );

        // 3. Roteiro prescrito (opcional, nunca obrigatório)
        $servico->salvar('roteiro_ativo', $requisicao->post('roteiro_ativo') === '1', $usuarioId);

        // 4. Local de permanência
        $local = $requisicao->post('local_permanencia', 'casa');
        $servico->salvar(
            'local_permanencia',
            in_array($local, ['casa', 'casa_escola', 'casa_creche', 'outro'], true) ? $local : 'casa',
            $usuarioId
        );

        // 5. Regra de edição
        $tipoRegra = $requisicao->post('regra_edicao', 'mesmo_dia');
        $servico->salvar('regra_edicao', [
            'tipo' => in_array($tipoRegra, ['livre', 'mesmo_dia', 'janela_minutos', 'somente_com_aprovacao'], true)
                ? $tipoRegra : 'mesmo_dia',
            'janela_minutos' => max(5, min(1440, (int)($requisicao->post('janela_minutos') ?? 60))),
        ], $usuarioId);

        // 6. Edição de dias anteriores
        $servico->salvar('edicao_dias_anteriores', $requisicao->post('edicao_dias_anteriores') === '1', $usuarioId);

        // 7. Alerta de omissão
        $servico->salvar('alerta_omissao', [
            'ativo' => $requisicao->post('alerta_ativo') === '1',
            'minutos' => max(15, min(720, (int)($requisicao->post('alerta_minutos') ?? 90))),
        ], $usuarioId);

        // 8. Fotos
        $fotos = $requisicao->post('fotos', 'opcional');
        $servico->salvar(
            'fotos',
            in_array($fotos, ['obrigatoria', 'opcional', 'desativada'], true) ? $fotos : 'opcional',
            $usuarioId
        );

        // 9. Resumo diário
        $canais = array_values(array_intersect((array)($_POST['resumo_canais'] ?? []), ['whatsapp', 'email', 'push']));
        $servico->salvar('resumo_diario', [
            'ativo' => $requisicao->post('resumo_ativo') === '1',
            'horario' => $hora($requisicao->post('resumo_horario'), '19:30'),
            'canais' => $canais === [] ? ['email'] : $canais,
        ], $usuarioId);

        // Grade: tolerância de atraso
        $servico->salvar(
            'tolerancia_atraso_minutos',
            max(0, min(120, (int)($requisicao->post('tolerancia_atraso') ?? 15))),
            $usuarioId
        );

        // Retenção
        $retencao = trim((string)$requisicao->post('retencao_meses', ''));
        $servico->salvar(
            'retencao_meses',
            $retencao === '' ? null : max(6, min(120, (int)$retencao)),
            $usuarioId
        );

        Sessao::flash('sucesso', 'Configurações salvas.');
        Resposta::redirecionarRota('config.familia');
    }

    // ── Usuários e convites ───────────────────────────────────
    public function usuarios(Requisicao $requisicao): void
    {
        $repositorio = new RepositorioUsuariosFamilia();
        Visao::exibir('config/usuarios', [
            'titulo' => 'Usuários e convites',
            'usuarios' => $repositorio->listar(),
            'convites' => $repositorio->listarConvites(),
            'linkConvite' => Sessao::obter('_link_convite'),
        ]);
        // (o link do convite recém-criado fica na sessão até ser exibido uma vez)
    }

    public function acaoUsuarios(Requisicao $requisicao): void
    {
        Sessao::remover('_link_convite');
        $servico = new ServicoUsuariosFamilia();
        $acao = (string)$requisicao->post('acao', '');
        $ip = $requisicao->ip();

        $erro = match ($acao) {
            'convidar' => (function () use ($requisicao, $servico, $ip): ?string {
                $resultado = $servico->convidar(
                    (string)$requisicao->post('email', ''),
                    (string)$requisicao->post('papel', ''),
                    $ip
                );
                if ($resultado['erro'] === null) {
                    Sessao::definir('_link_convite', $resultado['link']);
                    Sessao::flash('sucesso', 'Convite criado e enviado por e-mail.');
                }
                return $resultado['erro'];
            })(),
            'cancelar_convite' => $servico->cancelarConvite((int)$requisicao->post('convite_id', '0'), $ip),
            'ativar' => $servico->definirAtivo((string)$requisicao->post('usuario', ''), true, $ip),
            'desativar' => $servico->definirAtivo((string)$requisicao->post('usuario', ''), false, $ip),
            'papel' => $servico->alterarPapel(
                (string)$requisicao->post('usuario', ''),
                (string)$requisicao->post('novo_papel', ''),
                $ip
            ),
            default => 'Ação desconhecida.',
        };

        if ($erro !== null) {
            Sessao::flash('erro', $erro);
        } elseif ($acao !== 'convidar') {
            Sessao::flash('sucesso', 'Feito.');
        }
        Resposta::redirecionarRota('config.usuarios');
    }

    // ── Crianças ──────────────────────────────────────────────
    public function criancas(Requisicao $requisicao): void
    {
        $repositorio = new RepositorioCriancas();
        $editando = null;
        $codigoEdicao = $requisicao->get('editar');
        if (is_string($codigoEdicao) && $codigoEdicao !== '') {
            foreach ($repositorio->listar(false) as $crianca) {
                if ($crianca['codigo_publico'] === $codigoEdicao) {
                    $editando = $crianca;
                    break;
                }
            }
        }
        Visao::exibir('config/criancas', [
            'titulo' => 'Crianças',
            'criancas' => $repositorio->listar(false),
            'editando' => $editando,
        ]);
    }

    public function salvarCrianca(Requisicao $requisicao): void
    {
        $nome = trim((string)$requisicao->post('nome', ''));
        if ($nome === '') {
            Sessao::flash('erro', 'O nome da criança é obrigatório (os demais campos são opcionais).');
            Resposta::redirecionarRota('config.criancas');
        }

        $opcional = static function (?string $valor): ?string {
            $valor = trim((string)$valor);
            return $valor === '' ? null : $valor;
        };
        $data = $opcional($requisicao->post('data_nascimento'));
        if ($data !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            $data = null;
        }
        $sexo = $requisicao->post('sexo');
        $dados = [
            'nome' => $nome,
            'apelido' => $opcional($requisicao->post('apelido')),
            'data_nascimento' => $data,
            'sexo' => in_array($sexo, ['feminino', 'masculino', 'nao_informado'], true) ? $sexo : null,
            'tipo_sanguineo' => $opcional($requisicao->post('tipo_sanguineo')),
            'alergias' => $opcional($requisicao->post('alergias')),
            'condicoes_saude' => $opcional($requisicao->post('condicoes_saude')),
            'medicacoes_continuas' => $opcional($requisicao->post('medicacoes_continuas')),
            'pediatra_nome' => $opcional($requisicao->post('pediatra_nome')),
            'pediatra_telefone' => $opcional($requisicao->post('pediatra_telefone')),
            'ativo' => $requisicao->post('ativo', '1') === '1' ? 1 : 0,
        ];

        $repositorio = new RepositorioCriancas();
        $codigo = (string)$requisicao->post('codigo', '');
        if ($codigo !== '') {
            $existente = null;
            foreach ($repositorio->listar(false) as $crianca) {
                if ($crianca['codigo_publico'] === $codigo) {
                    $existente = $crianca;
                    break;
                }
            }
            if ($existente === null) {
                Sessao::flash('erro', 'Criança não encontrada.');
                Resposta::redirecionarRota('config.criancas');
            }
            $repositorio->atualizar((int)$existente['id'], $dados);
            Sessao::flash('sucesso', 'Dados de ' . $nome . ' atualizados.');
        } else {
            $repositorio->criar($nome, $dados);
            Sessao::flash('sucesso', $nome . ' cadastrada(o).');
        }
        Resposta::redirecionarRota('config.criancas');
    }

    // ── Categorias ativas ─────────────────────────────────────
    public function categorias(Requisicao $requisicao): void
    {
        $servicoConfig = new ServicoConfiguracoes();
        Visao::exibir('config/categorias', [
            'titulo' => 'Categorias de registro',
            'catalogo' => (new RepositorioCategorias())->catalogo(),
            'inativas' => (array)$servicoConfig->obter('categorias_inativas'),
            'acoesRapidas' => (array)$servicoConfig->obter('acoes_rapidas'),
        ]);
    }

    public function salvarCategorias(Requisicao $requisicao): void
    {
        $servicoConfig = new ServicoConfiguracoes();
        $catalogo = (new RepositorioCategorias())->catalogo();
        $slugsValidos = array_column($catalogo, 'slug');

        $ativas = (array)($_POST['ativas'] ?? []);
        $inativas = array_values(array_diff($slugsValidos, array_map('strval', $ativas)));
        $servicoConfig->salvar('categorias_inativas', $inativas, Autenticacao::id());

        $rapidas = array_values(array_intersect(
            array_map('strval', (array)($_POST['acoes_rapidas'] ?? [])),
            $slugsValidos
        ));
        $servicoConfig->salvar('acoes_rapidas', array_slice($rapidas, 0, 4), Autenticacao::id());

        Sessao::flash('sucesso', 'Categorias atualizadas.');
        Resposta::redirecionarRota('config.categorias');
    }
}
