<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Estado de autenticação da sessão atual.
 * A verificação de credenciais fica em Services\ServicoAutenticacao;
 * aqui só mora quem-está-logado e seu contexto de tenant.
 */
final class Autenticacao
{
    private const CHAVE = '_usuario';

    /**
     * @param array{id:int,familia_id:int,codigo_publico:string,nome:string,email:string,papel:string} $usuario
     */
    public static function autenticar(array $usuario): void
    {
        Sessao::regenerar();
        Sessao::definir(self::CHAVE, [
            'id'             => (int)$usuario['id'],
            'familia_id'     => (int)$usuario['familia_id'],
            'codigo_publico' => (string)$usuario['codigo_publico'],
            'nome'           => (string)$usuario['nome'],
            'email'          => (string)$usuario['email'],
            'papel'          => (string)$usuario['papel'],
        ]);
    }

    public static function sair(): void
    {
        Sessao::destruir();
    }

    public static function estaLogado(): bool
    {
        return is_array(Sessao::obter(self::CHAVE));
    }

    /** @return array{id:int,familia_id:int,codigo_publico:string,nome:string,email:string,papel:string}|null */
    public static function usuario(): ?array
    {
        $usuario = Sessao::obter(self::CHAVE);
        return is_array($usuario) ? $usuario : null;
    }

    public static function id(): int
    {
        return (int)(self::usuario()['id'] ?? 0);
    }

    public static function familiaId(): int
    {
        return (int)(self::usuario()['familia_id'] ?? 0);
    }

    public static function papel(): string
    {
        return (string)(self::usuario()['papel'] ?? '');
    }

    public static function temPapel(string ...$papeis): bool
    {
        return in_array(self::papel(), $papeis, true);
    }

    /** Marca na sessão que o consentimento LGPD vigente já foi verificado. */
    public static function marcarConsentimentoOk(): void
    {
        Sessao::definir('_lgpd_ok', true);
    }

    public static function consentimentoVerificado(): bool
    {
        return Sessao::obter('_lgpd_ok') === true;
    }
}
