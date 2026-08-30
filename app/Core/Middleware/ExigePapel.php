<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Autenticacao;
use App\Core\Requisicao;
use App\Core\Resposta;
use App\Core\Visao;

/**
 * Restringe a rota aos papéis informados ('papel:responsavel,admin_familia').
 * admin_familia herda implicitamente tudo que responsavel pode.
 */
final class ExigePapel
{
    /** @var string[] */
    private array $papeis;

    public function __construct(string ...$papeis)
    {
        $this->papeis = $papeis;
    }

    public function tratar(Requisicao $requisicao): void
    {
        $papel = Autenticacao::papel();

        $permitidos = $this->papeis;
        // Herança: admin_familia acessa tudo que é de responsavel
        if (in_array('responsavel', $permitidos, true) && !in_array('admin_familia', $permitidos, true)) {
            $permitidos[] = 'admin_familia';
        }

        if (in_array($papel, $permitidos, true)) {
            return;
        }
        if ($requisicao->ehApi()) {
            Resposta::erroJson('Sem permissão para esta ação.', 403);
        }
        Visao::erro403();
    }
}
