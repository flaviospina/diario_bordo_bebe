# Diário do Bebê

Webapp de controle da rotina diária de crianças pequenas: a babá/cuidador registra o dia
numa grade horária prescrita pelos pais, que acompanham tudo em tempo real.
Multi-tenant desde o primeiro commit. Todo o sistema em português do Brasil.

- **Stack:** PHP 8.2+/8.3, MySQL 8/MariaDB, HTML/CSS/JS vanilla, PWA. Sem framework, sem Composer, sem build step.
- **Planejamento aprovado:** [`docs/planejamento.md`](docs/planejamento.md) (árvore, DDL, rotas, decisões).

## Estado das fases

| Fase | Conteúdo | Estado |
|---|---|---|
| 1 | Fundação: front controller, roteador nomeado, `url()`, migrações web, seed de categorias, autenticação, papéis, multi-tenant, LGPD, layout, 404/403 | ✅ Entregue |
| 2 | Cadastros: usuários/convites, crianças, configurações da família | — |
| 3 | Núcleo: grade "Meu Dia", registros por schema JSON, auditoria, regras de edição | — |
| 4 | Offline/PWA: service worker, IndexedDB, sincronização idempotente | — |
| 5 | Pais e notificações: acompanhamento, omissão, intercorrências, n8n, resumo diário | — |
| 6 | Relatórios e comercialização | — |

## Deploy (HostGator, via FTP)

1. Envie todo o conteúdo do repositório para a pasta de destino
   (ex.: `public_html/diariobebe`) — funciona em subpasta ou domínio próprio
   sem alterar código (o `BASE_PATH` é autodetectado).
2. Copie `.env.example` para `.env` e preencha. Local recomendado: pasta
   `diariobebe_privado/` **irmã de `public_html`** (fora do webroot); o sistema
   procura lá primeiro. Como alternativa, o `.env` na raiz do projeto fica
   bloqueado pelo `.htaccess`.
3. Crie o banco MySQL e o usuário no cPanel; informe-os no `.env`.
4. Defina `MIGRATE_TOKEN` longo e aleatório no `.env` e acesse
   `https://SEU-DOMINIO/CAMINHO/install/migrate.php?token=SEU_TOKEN`
   para executar as migrações, popular as categorias globais e criar a
   família inicial com o primeiro administrador.
5. Entre em `/entrar`. Novos usuários entram somente por convite.

## Desenvolvimento local

```bash
# banco: MySQL/MariaDB local + .env apontando para ele
php -S localhost:8080 -t . caminho/para/router.php   # router que emula o .htaccess
```

Estrutura: `app/` (Core, Controllers, Services, Repositories, Views),
`config/` (rotas nomeadas), `database/` (migrações e seeds), `storage/`
(conteúdo fora da web), `install/migrate.php` (migrações via navegador).
Regra de ouro: **nenhuma URL literal** — todo link passa pelo helper `url()`
(PHP) ou `urlJs()` (JavaScript), e nenhuma query de dados roda sem
`familia_id` (imposto em `app/Repositories/RepositorioBase.php`).
