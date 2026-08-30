<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioCategorias;
use App\Repositories\RepositorioRegistros;
use App\Repositories\RepositorioVersoes;
use App\Services\ServicoConfiguracoes;
use App\Services\ServicoFotos;
use App\Services\ServicoGrade;
use App\Services\ServicoRegistros;

final class RegistroController
{
    // ── Criação ───────────────────────────────────────────────

    public function criarForm(Requisicao $requisicao): void
    {
        $categoria = $this->categoriaAtiva($requisicao->parametro('categoria'));
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->get('crianca'));
        if ($crianca === null) {
            Sessao::flash('erro', 'Cadastre uma criança antes de registrar.');
            Resposta::redirecionarRota('cuidador.dia');
        }
        Visao::exibir('registro/criar', [
            'titulo' => $categoria['nome'],
            'categoria' => $categoria,
            'schema' => json_decode((string)$categoria['schema_campos'], true) ?: ['campos' => []],
            'crianca' => $crianca,
            'blocoId' => (int)($requisicao->get('bloco') ?? 0),
            'dataPadrao' => $requisicao->get('data') ?? hoje(),
            'politicaFotos' => (string)(new ServicoConfiguracoes())->obter('fotos'),
        ]);
    }

    public function criarSalvar(Requisicao $requisicao): void
    {
        $categoria = $this->categoriaAtiva($requisicao->parametro('categoria'));
        $grade = new ServicoGrade();
        $crianca = $grade->criancaAtual($requisicao->post('crianca'));
        if ($crianca === null) {
            Resposta::redirecionarRota('cuidador.dia');
        }

        $servico = new ServicoRegistros();
        $status = in_array($requisicao->post('status'), ['feito', 'nao_feito', 'parcial'], true)
            ? (string)$requisicao->post('status') : 'feito';
        $justificativa = trim((string)$requisicao->post('justificativa', ''));
        if ($status === 'nao_feito' && $justificativa === '') {
            Sessao::flash('erro', 'Explique por que não foi feito (justificativa obrigatória).');
            Resposta::redirecionarRota('registro.criar', ['categoria' => $categoria['slug']]);
        }

        $validacao = $servico->validarCampos($categoria, $this->camposDinamicos($categoria), $status !== 'nao_feito');
        if ($validacao['erros'] !== []) {
            Sessao::flash('erro', implode(' ', $validacao['erros']));
            Resposta::redirecionarRota('registro.criar', ['categoria' => $categoria['slug']]);
        }

        $politicaFotos = (string)(new ServicoConfiguracoes())->obter('fotos');
        $temFoto = ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($politicaFotos === 'obrigatoria' && !$temFoto && $status !== 'nao_feito') {
            Sessao::flash('erro', 'A família configurou foto obrigatória neste diário.');
            Resposta::redirecionarRota('registro.criar', ['categoria' => $categoria['slug']]);
        }

        $resultado = $servico->criar($categoria, [
            'uuid_cliente' => $this->uuidValido($requisicao->post('uuid_cliente')),
            'crianca_id' => (int)$crianca['id'],
            'roteiro_bloco_id' => ((int)$requisicao->post('bloco', '0')) ?: null,
            'inicio' => $this->montarDataHora($requisicao, 'inicio') ?? agora(),
            'fim' => $this->montarDataHora($requisicao, 'fim'),
            'dados' => $validacao['dados'] === [] ? null : $validacao['dados'],
            'observacao' => trim((string)$requisicao->post('observacao', '')) ?: null,
            'status' => $status,
            'justificativa' => $justificativa !== '' ? $justificativa : null,
            'origem' => 'online',
        ], $requisicao->ip());

        if ($temFoto && $politicaFotos !== 'desativada' && !$resultado['duplicado']) {
            $erroFoto = (new ServicoFotos())->anexar($_FILES['foto'], (int)$resultado['registro']['id']);
            if ($erroFoto !== null) {
                Sessao::flash('aviso', 'Registro salvo, mas a foto falhou: ' . $erroFoto);
            }
        }

        $this->notificarIntercorrencia($resultado['intercorrencia']);
        Sessao::flash('sucesso', $categoria['nome'] . ' registrado.');
        $dataDoRegistro = substr((string)$resultado['registro']['inicio'], 0, 10);
        Resposta::redirecionarRota($dataDoRegistro === hoje() ? 'cuidador.dia' : 'cuidador.dia.data', $dataDoRegistro === hoje() ? [] : ['data' => $dataDoRegistro]);
    }

    // ── Consulta ──────────────────────────────────────────────

    public function ver(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        $servico = new ServicoRegistros();
        Visao::exibir('registro/ver', [
            'titulo' => $registro['categoria_nome'],
            'registro' => $registro,
            'dados' => json_decode((string)($registro['dados'] ?? 'null'), true) ?: [],
            'schema' => json_decode((string)$registro['schema_campos'], true) ?: ['campos' => []],
            'fotos' => (new ServicoFotos())->listarDoRegistro((int)$registro['id']),
            'permissao' => $registro['excluido_em'] !== null ? 'nada' : $servico->permissaoEdicao($registro),
            'versoes' => Autenticacao::temPapel('responsavel', 'admin_familia')
                ? (new RepositorioVersoes())->listarDoRegistro((int)$registro['id'])
                : [],
        ]);
    }

    // ── Edição direta ─────────────────────────────────────────

    public function editarForm(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        $permissao = (new ServicoRegistros())->permissaoEdicao($registro);
        if ($registro['excluido_em'] !== null || $permissao === 'nada') {
            Visao::erro403();
        }
        if ($permissao === 'solicitar') {
            // Fora da janela permitida o botão vira "Solicitar alteração" (regra 8.2)
            Resposta::redirecionarRota('registro.solicitar', ['codigo' => $registro['codigo_publico']]);
        }
        Visao::exibir('registro/editar', [
            'titulo' => 'Editar ' . $registro['categoria_nome'],
            'registro' => $registro,
            'dados' => json_decode((string)($registro['dados'] ?? 'null'), true) ?: [],
            'schema' => json_decode((string)$registro['schema_campos'], true) ?: ['campos' => []],
            'modo' => 'editar',
        ]);
    }

    public function editarSalvar(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        $servico = new ServicoRegistros();
        if ($registro['excluido_em'] !== null || $servico->permissaoEdicao($registro) !== 'editar') {
            Visao::erro403();
        }
        $campos = $this->coletarCamposEdicao($requisicao, $registro, $servico);
        if (is_string($campos)) {
            Sessao::flash('erro', $campos);
            Resposta::redirecionarRota('registro.editar', ['codigo' => $registro['codigo_publico']]);
        }
        $servico->editar($registro, $campos, trim((string)$requisicao->post('motivo', '')) ?: null, $requisicao->ip());
        Sessao::flash('sucesso', 'Registro atualizado (a versão anterior fica guardada na auditoria).');
        Resposta::redirecionarRota('registro.ver', ['codigo' => $registro['codigo_publico']]);
    }

    public function excluir(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        $servico = new ServicoRegistros();
        $motivo = trim((string)$requisicao->post('motivo', ''));
        if ($motivo === '') {
            Sessao::flash('erro', 'Informe o motivo da exclusão (obrigatório).');
            Resposta::redirecionarRota('registro.ver', ['codigo' => $registro['codigo_publico']]);
        }
        $permissao = $registro['excluido_em'] !== null ? 'nada' : $servico->permissaoEdicao($registro);
        if ($permissao === 'editar') {
            $servico->excluir($registro, $motivo, $requisicao->ip());
            Sessao::flash('sucesso', 'Registro excluído (exclusão lógica — o histórico permanece).');
        } elseif ($permissao === 'solicitar') {
            $servico->solicitarAlteracao($registro, $motivo, ['excluir' => true], 'exclusao');
            Sessao::flash('sucesso', 'Solicitação de exclusão enviada aos responsáveis.');
        } else {
            Visao::erro403();
        }
        Resposta::redirecionarRota('cuidador.dia');
    }

    // ── Solicitação de alteração ──────────────────────────────

    public function solicitarForm(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        if ($registro['excluido_em'] !== null) {
            Visao::erro403();
        }
        Visao::exibir('registro/editar', [
            'titulo' => 'Solicitar alteração',
            'registro' => $registro,
            'dados' => json_decode((string)($registro['dados'] ?? 'null'), true) ?: [],
            'schema' => json_decode((string)$registro['schema_campos'], true) ?: ['campos' => []],
            'modo' => 'solicitar',
        ]);
    }

    public function solicitarEnviar(Requisicao $requisicao): void
    {
        $registro = $this->registroDaRota($requisicao);
        $servico = new ServicoRegistros();
        $motivo = trim((string)$requisicao->post('motivo', ''));
        if ($motivo === '') {
            Sessao::flash('erro', 'Explique o motivo da alteração.');
            Resposta::redirecionarRota('registro.solicitar', ['codigo' => $registro['codigo_publico']]);
        }
        $campos = $this->coletarCamposEdicao($requisicao, $registro, $servico);
        if (is_string($campos)) {
            Sessao::flash('erro', $campos);
            Resposta::redirecionarRota('registro.solicitar', ['codigo' => $registro['codigo_publico']]);
        }
        $servico->solicitarAlteracao($registro, $motivo, $campos);
        Sessao::flash('sucesso', 'Solicitação enviada aos responsáveis para aprovação.');
        Resposta::redirecionarRota('registro.ver', ['codigo' => $registro['codigo_publico']]);
    }

    // ── Auxiliares ────────────────────────────────────────────

    private function categoriaAtiva(string $slug): array
    {
        $categoria = (new RepositorioCategorias())->buscarPorSlug($slug);
        $inativas = (array)(new ServicoConfiguracoes())->obter('categorias_inativas');
        if ($categoria === null || in_array($categoria['slug'], $inativas, true)) {
            Visao::erro404();
        }
        return $categoria;
    }

    private function registroDaRota(Requisicao $requisicao): array
    {
        $registro = (new RepositorioRegistros())->buscarPorCodigo($requisicao->parametro('codigo'));
        if ($registro === null) {
            Visao::erro404();
        }
        return $registro;
    }

    /** Campos dinâmicos vêm com prefixo c_ para não colidir com os fixos. */
    private function camposDinamicos(array $categoria): array
    {
        $schema = json_decode((string)$categoria['schema_campos'], true) ?: [];
        $entrada = [];
        foreach (($schema['campos'] ?? []) as $campo) {
            $valor = $_POST['c_' . $campo['nome']] ?? null;
            if (is_string($valor)) {
                $entrada[$campo['nome']] = $valor;
            }
        }
        return $entrada;
    }

    /** @return array<string,mixed>|string campos normalizados ou mensagem de erro */
    private function coletarCamposEdicao(Requisicao $requisicao, array $registro, ServicoRegistros $servico): array|string
    {
        $status = in_array($requisicao->post('status'), ['feito', 'nao_feito', 'parcial'], true)
            ? (string)$requisicao->post('status') : (string)$registro['status'];
        $justificativa = trim((string)$requisicao->post('justificativa', ''));
        if ($status === 'nao_feito' && $justificativa === '') {
            return 'Explique por que não foi feito (justificativa obrigatória).';
        }
        $categoria = [
            'schema_campos' => $registro['schema_campos'],
        ];
        $validacao = $servico->validarCampos($categoria, $this->camposDinamicos($categoria), $status !== 'nao_feito');
        if ($validacao['erros'] !== []) {
            return implode(' ', $validacao['erros']);
        }
        return [
            'inicio' => $this->montarDataHora($requisicao, 'inicio') ?? (string)$registro['inicio'],
            'fim' => $this->montarDataHora($requisicao, 'fim'),
            'dados' => $validacao['dados'] === [] ? null : $validacao['dados'],
            'observacao' => trim((string)$requisicao->post('observacao', '')) ?: null,
            'status' => $status,
            'justificativa' => $justificativa !== '' ? $justificativa : null,
        ];
    }

    /** Junta data + hora dos inputs (ex.: inicio_data / inicio_hora). */
    private function montarDataHora(Requisicao $requisicao, string $prefixo): ?string
    {
        $data = (string)$requisicao->post($prefixo . '_data', '');
        $hora = (string)$requisicao->post($prefixo . '_hora', '');
        if ($data === '' || $hora === ''
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1
            || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) !== 1) {
            return null;
        }
        return $data . ' ' . $hora . ':00';
    }

    private function uuidValido(?string $uuid): ?string
    {
        return is_string($uuid) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid) === 1
            ? $uuid : null;
    }

    /** Gancho da Fase 5: intercorrência grave dispara notificação imediata. */
    private function notificarIntercorrencia(?array $intercorrencia): void
    {
        if ($intercorrencia === null) {
            return;
        }
        if (class_exists(\App\Services\ServicoNotificacoes::class)) {
            (new \App\Services\ServicoNotificacoes())->notificarIntercorrencia(
                $intercorrencia['codigo'],
                $intercorrencia['gravidade']
            );
        }
    }
}
