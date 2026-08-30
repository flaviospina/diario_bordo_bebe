<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Sessao;
use App\Core\Visao;
use App\Repositories\RepositorioPerfis;

/**
 * Perfil complementar do próprio usuário (dados dos pais/responsáveis).
 * Todos os campos são opcionais — preenchimento nunca é obrigatório.
 */
final class PerfilController
{
    public function editar(Requisicao $requisicao): void
    {
        Visao::exibir('perfil/editar', [
            'titulo' => 'Meu perfil',
            'usuario' => Autenticacao::usuario(),
            'perfil' => (new RepositorioPerfis())->buscarPorUsuario(Autenticacao::id()) ?? [],
        ]);
    }

    public function salvar(Requisicao $requisicao): void
    {
        $opcional = static function (?string $valor): ?string {
            $valor = trim((string)$valor);
            return $valor === '' ? null : mb_substr($valor, 0, 255);
        };
        $data = $opcional($requisicao->post('data_nascimento'));
        if ($data !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) !== 1) {
            $data = null;
        }

        (new RepositorioPerfis())->salvar(Autenticacao::id(), [
            'cpf' => $opcional($requisicao->post('cpf')),
            'data_nascimento' => $data,
            'profissao' => $opcional($requisicao->post('profissao')),
            'telefone_alternativo' => $opcional($requisicao->post('telefone_alternativo')),
            'endereco' => $opcional($requisicao->post('endereco')),
            'contato_emergencia_nome' => $opcional($requisicao->post('contato_emergencia_nome')),
            'contato_emergencia_telefone' => $opcional($requisicao->post('contato_emergencia_telefone')),
            'observacoes' => $opcional($requisicao->post('observacoes')),
        ]);

        Sessao::flash('sucesso', 'Perfil atualizado.');
        Resposta::redirecionarRota('perfil.editar');
    }
}
