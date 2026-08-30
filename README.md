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

Guia completo e detalhado: [`docs/deploy-hostgator.md`](docs/deploy-hostgator.md).
Resumo: enviar os arquivos para `DOCUMENT_ROOT/diariobebe/` (File Manager, FTP
ou `scripts/deploy_ftp.py`), criar banco+usuário no cPanel, criar o `.env` na
pasta `diariobebe_privado/` na home da conta (fora do webroot) e abrir
`install/migrate.php?token=MIGRATE_TOKEN` no navegador — ele cria todas as
tabelas, popula as categorias globais e cadastra a família inicial com o
primeiro administrador. Funciona em subpasta ou domínio próprio sem alterar
código (`BASE_PATH` autodetectado).

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
