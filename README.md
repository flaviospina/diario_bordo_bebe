# Diário do Bebê

Webapp de controle da rotina diária de crianças pequenas: a babá/cuidador registra o dia
numa grade horária prescrita pelos pais, que acompanham tudo em tempo real.
Multi-tenant desde o primeiro commit. Todo o sistema em português do Brasil.

- **Stack:** PHP 8.2+/8.3, MySQL 8/MariaDB, HTML/CSS/JS vanilla, PWA. Sem framework, sem Composer, sem build step.
- **Planejamento aprovado:** [`docs/planejamento.md`](docs/planejamento.md) (árvore, DDL, rotas, decisões).

## Estado das fases — todas entregues ✅

| Fase | Conteúdo | Estado |
|---|---|---|
| 1 | Fundação: front controller, roteador nomeado, `url()`, migrações web, seed de categorias, autenticação, papéis, multi-tenant, LGPD, layout, 404/403 | ✅ |
| 2 | Cadastros: usuários/convites, crianças, perfis, painel completo de configurações versionadas | ✅ |
| 3 | Núcleo: grade "Meu Dia" (roteiro/slots/flexível), registros por schema JSON, voz, fotos, auditoria imutável, regras de edição, solicitações | ✅ |
| 4 | Offline/PWA: service worker + manifest com BASE_PATH, IndexedDB, sincronização idempotente, conflito → revisão | ✅ |
| 5 | Pais e notificações: Acompanhar com polling, semáforo de omissão, ciência de intercorrências, n8n (fila + backoff + callback), resumo diário em linguagem natural | ✅ |
| 6 | Relatórios (7/30 dias, Modo Pediatra, CSV/PDF), exportação LGPD, painel super_admin com onboarding/planos/suspensão/exclusão | ✅ |

## Tarefas agendadas (cron do cPanel ou n8n)

A cada 5 minutos (e `expurgo` 1x/dia), com o `TAREFAS_TOKEN` do `.env`:

```
curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/fila
curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/omissao
curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/resumo
curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/expurgo
```

O n8n recebe as notificações em `N8N_WEBHOOK_URL` (header `X-Api-Key`) e confirma a
entrega em `POST /api/webhook/status` com `{"notificacao_id":N,"status":"entregue"|"falha"}`.

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
