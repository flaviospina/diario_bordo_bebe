# Diário do Bebê — Planejamento Técnico (pré-Fase 1)

Documento de aprovação exigido antes do início da Fase 1. Contém:

1. Árvore de diretórios completa do projeto
2. DDL final revisado, com índices e chaves estrangeiras
3. `.htaccess` e tabela de rotas, com confirmação de funcionamento em subpasta
4. Pontos ambíguos ou arriscados do escopo, com a decisão proposta para cada um

---

## 1. Árvore de diretórios

O projeto inteiro vive dentro de uma única pasta implantável por FTP (`/diariobebe` em
`public_html`, ou a raiz de um domínio próprio). Não há build step nem passo de terminal.

```
diariobebe/
├── .htaccess                  # front controller + bloqueio de pastas internas + headers
├── .env.example               # modelo de configuração (o .env real nunca vai ao Git)
├── index.php                  # front controller único — todo acesso passa por aqui
│
├── install/
│   └── migrate.php            # migrações via navegador, protegidas por token do .env
│
├── assets/                    # únicos arquivos servidos estaticamente (além de sw/manifest)
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── base.js            # bootstrap do front: lê window.APP.basePath, helper urlJs()
│   │   ├── grade.js           # tela "Meu Dia" (Fase 3)
│   │   ├── acompanhar.js      # tela dos pais + polling (Fase 5)
│   │   ├── offline.js         # IndexedDB, fila de sincronização (Fase 4)
│   │   ├── voz.js             # Web Speech API com fallback (Fase 3)
│   │   └── formularios.js     # renderização de formulário a partir de schema_campos JSON
│   ├── img/
│   │   └── icones/            # ícones do PWA (192, 512, maskable) e de categorias
│   └── fontes/                # se necessário; nada de CDN externo
│
├── app/                       # BLOQUEADO por .htaccess
│   ├── Core/
│   │   ├── Aplicacao.php      # bootstrap: env, sessão, roteador, tratamento de erro
│   │   ├── Ambiente.php       # parser do .env, com busca fora do webroot (ver §4.1)
│   │   ├── Roteador.php       # rotas nomeadas, parâmetros, métodos, middlewares
│   │   ├── Requisicao.php
│   │   ├── Resposta.php       # redirect, json, 301 canônico, download
│   │   ├── Visao.php          # render de views com escape por padrão
│   │   ├── BancoDados.php     # PDO singleton, prepared statements
│   │   ├── Sessao.php         # cookie HttpOnly/Secure/SameSite=Lax
│   │   ├── Csrf.php
│   │   ├── Autenticacao.php   # login, Argon2id, rate limit
│   │   ├── Identificadores.php# ULID / codigo_publico / slugs
│   │   └── Middleware/
│   │       ├── ExigeAutenticacao.php
│   │       ├── ExigePapel.php
│   │       └── ExigeConsentimentoLgpd.php
│   ├── Controllers/
│   │   ├── AutenticacaoController.php
│   │   ├── ConviteController.php
│   │   ├── CuidadorController.php
│   │   ├── RegistroController.php
│   │   ├── PaisController.php
│   │   ├── CriancaController.php
│   │   ├── RoteiroController.php
│   │   ├── IntercorrenciaController.php
│   │   ├── SolicitacaoController.php
│   │   ├── RelatorioController.php
│   │   ├── ConfiguracaoController.php
│   │   ├── AuditoriaController.php
│   │   ├── SuprimentoController.php
│   │   ├── TurnoController.php
│   │   ├── PainelAdminController.php   # super_admin
│   │   ├── ArquivoController.php       # /foto/{codigo}, /download/{codigo}
│   │   ├── PwaController.php           # /manifest.webmanifest, /sw.js (ver §4.3)
│   │   └── Api/
│   │       ├── RegistroApiController.php
│   │       ├── SincronizacaoApiController.php
│   │       ├── WebhookApiController.php
│   │       └── TarefaApiController.php # cron web: omissão, resumo, fila (ver §4.5)
│   ├── Services/              # regra de negócio; controllers finos
│   ├── Repositories/
│   │   ├── RepositorioBase.php# TODA query recebe familia_id obrigatório (ver §2.1)
│   │   └── ...um por agregado
│   ├── Helpers/
│   │   └── funcoes.php        # url(), e() [escape], data_br(), etc.
│   └── Views/
│       ├── layouts/ (base.php, autenticacao.php)
│       ├── erros/ (404.php, 403.php)
│       └── ...uma pasta por área (autenticacao, cuidador, pais, config, ...)
│
├── config/                    # BLOQUEADO por .htaccess
│   ├── app.php                # constantes derivadas do .env (BASE_PATH etc.)
│   └── rotas.php              # definição central de TODAS as rotas nomeadas
│
├── database/                  # BLOQUEADO por .htaccess
│   ├── migrations/            # 0001_familias.php, 0002_usuarios.php... idempotentes
│   └── seeds/
│       └── categorias_globais.php
│
├── storage/                   # BLOQUEADO por .htaccess (fotos servidas só via /foto/{codigo})
│   ├── fotos/
│   ├── thumbs/
│   ├── pdfs/
│   ├── exportacoes/
│   ├── logs/
│   └── cache/
│
└── docs/
    └── planejamento.md        # este documento
```

Observação: `storage/` idealmente fica **fora** do webroot (ver risco §4.1); a estrutura acima
é o fallback para HostGator em subpasta, com bloqueio duplo (`.htaccess` na raiz + `.htaccess`
"Deny all" dentro de cada pasta interna, para sobreviver a sobrescrita acidental do principal).

---

## 2. DDL final revisado

Revisões em relação ao rascunho da seção 5 do briefing (todas justificadas):

- **`codigo_publico` e `slug`** adicionados onde a URL exige (`criancas`, `registros`,
  `intercorrencias`, `solicitacoes_edicao`, `resumos_diarios`, `registro_fotos`, `arquivos_gerados`).
- **Tabelas novas necessárias ao próprio briefing:** `convites` (rota `/convite/{token}`),
  `tokens_senha` (rota `/senha/redefinir/{token}`), `tentativas_login` (rate limit),
  `arquivos_gerados` (rota `/download/{codigo}`).
- **Exclusão lógica** em `registros` (`excluido_em`, `excluido_por`, `motivo_exclusao`) — regra 8.1.
- `usuarios.email` único **global** (decisão discutida em §4.6).
- Engine InnoDB, `utf8mb4_unicode_ci`, FKs com `ON DELETE RESTRICT` por padrão (nada some em
  cascata num sistema de auditoria); exceções pontuais comentadas.

```sql
-- =============================================================
-- 0001 — Núcleo multi-tenant
-- =============================================================
CREATE TABLE familias (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,
    slug            VARCHAR(80)     NOT NULL,
    nome            VARCHAR(120)    NOT NULL,
    plano           VARCHAR(30)     NOT NULL DEFAULT 'familiar',
    status          ENUM('ativa','suspensa','encerrada') NOT NULL DEFAULT 'ativa',
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_familias_codigo (codigo_publico),
    UNIQUE KEY uq_familias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    codigo_publico  CHAR(12)        NOT NULL,
    nome            VARCHAR(120)    NOT NULL,
    email           VARCHAR(190)    NOT NULL,
    senha_hash      VARCHAR(255)    NOT NULL,
    papel           ENUM('admin_familia','responsavel','cuidador','leitor','super_admin') NOT NULL,
    telefone_whatsapp VARCHAR(20)   NULL,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    ultimo_login    DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_codigo (codigo_publico),
    KEY ix_usuarios_familia (familia_id, papel, ativo),
    CONSTRAINT fk_usuarios_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- super_admin fica em uma família técnica "plataforma" (id 1), sem dados de crianças.

CREATE TABLE perfis_responsaveis (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    cpf             VARCHAR(14)     NULL,
    data_nascimento DATE            NULL,
    profissao       VARCHAR(120)    NULL,
    telefone_alternativo VARCHAR(20) NULL,
    endereco        VARCHAR(255)    NULL,
    contato_emergencia_nome     VARCHAR(120) NULL,
    contato_emergencia_telefone VARCHAR(20)  NULL,
    observacoes     TEXT            NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_perfis_usuario (usuario_id),
    CONSTRAINT fk_perfis_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE criancas (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    codigo_publico  CHAR(12)        NOT NULL,
    slug            VARCHAR(80)     NOT NULL,          -- único por família; colisão => sufixo -2
    nome            VARCHAR(120)    NOT NULL,
    apelido         VARCHAR(60)     NULL,
    data_nascimento DATE            NULL,
    sexo            ENUM('feminino','masculino','nao_informado') NULL,
    foto_path       VARCHAR(255)    NULL,
    tipo_sanguineo  VARCHAR(3)      NULL,
    alergias        TEXT            NULL,
    condicoes_saude TEXT            NULL,
    medicacoes_continuas TEXT       NULL,
    pediatra_nome   VARCHAR(120)    NULL,
    pediatra_telefone VARCHAR(20)   NULL,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_criancas_codigo (codigo_publico),
    UNIQUE KEY uq_criancas_slug (familia_id, slug),
    KEY ix_criancas_familia (familia_id, ativo),
    CONSTRAINT fk_criancas_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 0002 — Configurações versionadas
-- =============================================================
CREATE TABLE configuracoes_familia (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    chave           VARCHAR(60)     NOT NULL,
    valor           JSON            NOT NULL,
    atualizado_por  BIGINT UNSIGNED NULL,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_config_familia_chave (familia_id, chave),
    CONSTRAINT fk_config_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_config_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes_historico (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    chave           VARCHAR(60)     NOT NULL,
    valor_anterior  JSON            NULL,
    valor_novo      JSON            NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_confhist_familia (familia_id, chave, criado_em),
    CONSTRAINT fk_confhist_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_confhist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 0003 — Categorias e roteiro
-- =============================================================
CREATE TABLE categorias (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NULL,              -- NULL = catálogo global
    grupo           VARCHAR(40)     NOT NULL,          -- alimentacao, sono, higiene...
    nome            VARCHAR(80)     NOT NULL,
    slug            VARCHAR(80)     NOT NULL,
    icone           VARCHAR(40)     NULL,
    cor             CHAR(7)         NULL,              -- #RRGGBB
    schema_campos   JSON            NOT NULL,          -- dirige o formulário; nada hardcoded
    ordem           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_slug (familia_id, slug), -- MySQL permite NULL repetido; slugs globais
                                                      -- garantidos únicos pelo seed
    KEY ix_categorias_grupo (grupo, ordem),
    CONSTRAINT fk_categorias_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roteiro_blocos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    crianca_id      BIGINT UNSIGNED NOT NULL,
    dias_semana     SET('dom','seg','ter','qua','qui','sex','sab') NOT NULL,
    hora_inicio     TIME            NOT NULL,
    hora_fim        TIME            NOT NULL,
    titulo          VARCHAR(120)    NOT NULL,
    categoria_id    BIGINT UNSIGNED NULL,
    instrucao       TEXT            NULL,
    obrigatorio     TINYINT(1)      NOT NULL DEFAULT 0,
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_roteiro_crianca (familia_id, crianca_id, ativo, hora_inicio),
    CONSTRAINT fk_roteiro_familia  FOREIGN KEY (familia_id)  REFERENCES familias(id),
    CONSTRAINT fk_roteiro_crianca  FOREIGN KEY (crianca_id)  REFERENCES criancas(id),
    CONSTRAINT fk_roteiro_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 0004 — Registros, versões e fotos
-- =============================================================
CREATE TABLE registros (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,
    uuid_cliente    CHAR(36)        NOT NULL,          -- idempotência offline (regra 8.3)
    familia_id      BIGINT UNSIGNED NOT NULL,
    crianca_id      BIGINT UNSIGNED NOT NULL,
    categoria_id    BIGINT UNSIGNED NOT NULL,
    roteiro_bloco_id BIGINT UNSIGNED NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    inicio          DATETIME        NOT NULL,
    fim             DATETIME        NULL,
    dados           JSON            NULL,
    observacao      TEXT            NULL,
    status          ENUM('feito','nao_feito','parcial') NOT NULL DEFAULT 'feito',
    justificativa   TEXT            NULL,              -- obrigatória quando nao_feito
    origem          ENUM('online','offline') NOT NULL DEFAULT 'online',
    excluido_em     DATETIME        NULL,              -- exclusão SEMPRE lógica (regra 8.1)
    excluido_por    BIGINT UNSIGNED NULL,
    motivo_exclusao TEXT            NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registros_uuid (uuid_cliente),
    UNIQUE KEY uq_registros_codigo (codigo_publico),
    KEY ix_registros_dia (familia_id, crianca_id, inicio),         -- grade do dia
    KEY ix_registros_categoria (familia_id, categoria_id, inicio), -- relatórios
    KEY ix_registros_omissao (familia_id, criado_em),              -- alerta de omissão
    CONSTRAINT fk_registros_familia   FOREIGN KEY (familia_id)   REFERENCES familias(id),
    CONSTRAINT fk_registros_crianca   FOREIGN KEY (crianca_id)   REFERENCES criancas(id),
    CONSTRAINT fk_registros_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_registros_bloco     FOREIGN KEY (roteiro_bloco_id) REFERENCES roteiro_blocos(id),
    CONSTRAINT fk_registros_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
    CONSTRAINT fk_registros_excluidor FOREIGN KEY (excluido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registro_fotos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,          -- rota /foto/{codigo}
    registro_id     BIGINT UNSIGNED NOT NULL,
    caminho         VARCHAR(255)    NOT NULL,          -- relativo a storage/, nunca exposto
    thumb           VARCHAR(255)    NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fotos_codigo (codigo_publico),
    KEY ix_fotos_registro (registro_id),
    CONSTRAINT fk_fotos_registro FOREIGN KEY (registro_id) REFERENCES registros(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registro_versoes (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id     BIGINT UNSIGNED NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    dados_anteriores JSON           NOT NULL,          -- snapshot completo do registro
    dados_novos     JSON            NOT NULL,
    motivo          VARCHAR(255)    NULL,
    ip              VARCHAR(45)     NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_versoes_registro (registro_id, criado_em),
    CONSTRAINT fk_versoes_registro FOREIGN KEY (registro_id) REFERENCES registros(id),
    CONSTRAINT fk_versoes_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Tabela imutável: aplicação só faz INSERT; sem UPDATE/DELETE em nenhum caminho de código.

CREATE TABLE solicitacoes_edicao (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,
    familia_id      BIGINT UNSIGNED NOT NULL,
    registro_id     BIGINT UNSIGNED NOT NULL,
    solicitante_id  BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('edicao','exclusao','conflito_sync') NOT NULL DEFAULT 'edicao',
    motivo          TEXT            NOT NULL,
    payload_proposto JSON           NOT NULL,
    status          ENUM('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
    decidido_por    BIGINT UNSIGNED NULL,
    decidido_em     DATETIME        NULL,
    resposta        TEXT            NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_solicitacoes_codigo (codigo_publico),
    KEY ix_solicitacoes_pendentes (familia_id, status, criado_em),
    CONSTRAINT fk_solic_familia     FOREIGN KEY (familia_id)     REFERENCES familias(id),
    CONSTRAINT fk_solic_registro    FOREIGN KEY (registro_id)    REFERENCES registros(id),
    CONSTRAINT fk_solic_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    CONSTRAINT fk_solic_decisor     FOREIGN KEY (decidido_por)   REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- tipo 'conflito_sync' cobre a regra 8.4 (conflito offline vira revisão para os pais).

-- =============================================================
-- 0005 — Intercorrências, turnos, suprimentos
-- =============================================================
CREATE TABLE intercorrencias (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,
    familia_id      BIGINT UNSIGNED NOT NULL,
    crianca_id      BIGINT UNSIGNED NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    ocorrido_em     DATETIME        NOT NULL,
    gravidade       ENUM('leve','moderada','grave') NOT NULL,
    descricao       TEXT            NOT NULL,
    acao_tomada     TEXT            NULL,
    ciencia_usuario_id BIGINT UNSIGNED NULL,           -- ciência formal dos pais
    ciencia_em      DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_intercorrencias_codigo (codigo_publico),
    KEY ix_intercorrencias_familia (familia_id, ocorrido_em),
    KEY ix_intercorrencias_sem_ciencia (familia_id, ciencia_em),
    CONSTRAINT fk_interc_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_interc_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id),
    CONSTRAINT fk_interc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_interc_ciencia FOREIGN KEY (ciencia_usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE turnos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    entrada         DATETIME        NOT NULL,
    saida           DATETIME        NULL,
    entrada_manual  TINYINT(1)      NOT NULL DEFAULT 0, -- ajuste manual é auditado (regra 8.8)
    observacao      TEXT            NULL,
    PRIMARY KEY (id),
    KEY ix_turnos_familia (familia_id, entrada),
    KEY ix_turnos_usuario (usuario_id, entrada),
    CONSTRAINT fk_turnos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_turnos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suprimentos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    item            VARCHAR(120)    NOT NULL,
    nivel           ENUM('ok','baixo','acabou') NOT NULL DEFAULT 'baixo',
    solicitado_por  BIGINT UNSIGNED NOT NULL,
    solicitado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolvido_em    DATETIME        NULL,
    PRIMARY KEY (id),
    KEY ix_suprimentos_abertos (familia_id, resolvido_em),
    CONSTRAINT fk_supr_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_supr_usuario FOREIGN KEY (solicitado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- 0006 — Resumos, notificações, LGPD, auditoria de acesso
-- =============================================================
CREATE TABLE resumos_diarios (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,
    familia_id      BIGINT UNSIGNED NOT NULL,
    crianca_id      BIGINT UNSIGNED NOT NULL,
    data            DATE            NOT NULL,
    texto_gerado    MEDIUMTEXT      NULL,
    pdf_path        VARCHAR(255)    NULL,
    enviado_em      DATETIME        NULL,
    canais          JSON            NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resumos_codigo (codigo_publico),
    UNIQUE KEY uq_resumos_dia (crianca_id, data),
    KEY ix_resumos_familia (familia_id, data),
    CONSTRAINT fk_resumos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_resumos_crianca FOREIGN KEY (crianca_id) REFERENCES criancas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fila_notificacoes (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    evento          VARCHAR(40)     NOT NULL,  -- resumo_diario, alerta_omissao, intercorrencia...
    canal           ENUM('whatsapp','email','push') NOT NULL,
    destinatario    VARCHAR(190)    NOT NULL,
    payload         JSON            NOT NULL,
    status          ENUM('pendente','enviando','enviada','falha','cancelada') NOT NULL DEFAULT 'pendente',
    tentativas      TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- backoff, máx. 3 (seção 9)
    ultimo_erro     TEXT            NULL,
    agendado_para   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviado_em      DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_fila_pendentes (status, agendado_para),
    KEY ix_fila_familia (familia_id, criado_em),
    CONSTRAINT fk_fila_familia FOREIGN KEY (familia_id) REFERENCES familias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consentimentos_lgpd (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('responsavel','cuidador','leitor') NOT NULL,
    versao_termo    VARCHAR(20)     NOT NULL,
    aceito_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip              VARCHAR(45)     NULL,
    PRIMARY KEY (id),
    KEY ix_lgpd_usuario (usuario_id, tipo, versao_termo),
    CONSTRAINT fk_lgpd_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE log_acessos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NULL,      -- NULL para eventos pré-login (falha de login)
    usuario_id      BIGINT UNSIGNED NULL,
    acao            VARCHAR(60)     NOT NULL,
    entidade        VARCHAR(60)     NULL,
    entidade_id     BIGINT UNSIGNED NULL,
    ip              VARCHAR(45)     NULL,
    user_agent      VARCHAR(255)    NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_log_familia (familia_id, criado_em),
    KEY ix_log_usuario (usuario_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Sem FKs de propósito: log não pode impedir nenhuma operação nem ser apagado em cascata.

-- =============================================================
-- 0007 — Tabelas de suporte exigidas pelas rotas do briefing
-- =============================================================
CREATE TABLE convites (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    familia_id      BIGINT UNSIGNED NOT NULL,
    email           VARCHAR(190)    NOT NULL,
    papel           ENUM('admin_familia','responsavel','cuidador','leitor') NOT NULL,
    token_hash      CHAR(64)        NOT NULL,          -- sha256 do token; token puro só no link
    convidado_por   BIGINT UNSIGNED NOT NULL,
    expira_em       DATETIME        NOT NULL,
    aceito_em       DATETIME        NULL,
    usuario_criado_id BIGINT UNSIGNED NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_convites_token (token_hash),
    KEY ix_convites_familia (familia_id, aceito_em),
    CONSTRAINT fk_convites_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_convites_autor   FOREIGN KEY (convidado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens_senha (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      BIGINT UNSIGNED NOT NULL,
    token_hash      CHAR(64)        NOT NULL,
    expira_em       DATETIME        NOT NULL,
    usado_em        DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tokens_senha (token_hash),
    KEY ix_tokens_usuario (usuario_id),
    CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tentativas_login (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(190)    NOT NULL,
    ip              VARCHAR(45)     NOT NULL,
    sucesso         TINYINT(1)      NOT NULL DEFAULT 0,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_tentativas_email (email, criado_em),
    KEY ix_tentativas_ip (ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE arquivos_gerados (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_publico  CHAR(12)        NOT NULL,          -- rota /download/{codigo}
    familia_id      BIGINT UNSIGNED NOT NULL,
    tipo            ENUM('pdf','csv') NOT NULL,
    descricao       VARCHAR(160)    NOT NULL,
    caminho         VARCHAR(255)    NOT NULL,
    gerado_por      BIGINT UNSIGNED NOT NULL,
    expira_em       DATETIME        NULL,
    criado_em       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_arquivos_codigo (codigo_publico),
    KEY ix_arquivos_familia (familia_id, criado_em),
    CONSTRAINT fk_arquivos_familia FOREIGN KEY (familia_id) REFERENCES familias(id),
    CONSTRAINT fk_arquivos_usuario FOREIGN KEY (gerado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE migracoes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    arquivo         VARCHAR(120) NOT NULL,
    executado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migracoes_arquivo (arquivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.1 Isolamento multi-tenant na camada de repositório

`RepositorioBase` recebe o **contexto do tenant** no construtor (objeto `ContextoTenant` com o
`familia_id` da sessão). Métodos de consulta montam o `WHERE familia_id = ?` internamente;
nenhum método público aceita `familia_id` vindo de request. `super_admin` usa repositórios de
plataforma separados que não têm acesso às tabelas de conteúdo (registros, fotos, intercorrências).

---

## 3. `.htaccess` e rotas

### 3.1 `.htaccess` (raiz do projeto)

```apache
# ── Diário do Bebê ─────────────────────────────────────────────
Options -Indexes -MultiViews
DirectoryIndex index.php

<IfModule mod_rewrite.c>
    RewriteEngine On
    # SEM RewriteBase fixo: substituições relativas funcionam por diretório,
    # então o MESMO arquivo serve em /diariobebe e em domínio próprio (ver §4.2).

    # 1) Bloqueio de pastas internas e arquivos sensíveis
    RewriteRule ^(app|config|database|storage|docs)(/|$) - [F,L]
    RewriteRule (^|/)\.(env|git|htaccess) - [F,L]

    # 2) Estáticos existentes passam direto (assets/, install/migrate.php)
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # 3) Todo o resto cai no front controller
    RewriteRule ^ index.php [L]
</IfModule>

# Defesa em profundidade caso mod_rewrite falhe
<FilesMatch "^\.env">
    Require all denied
</FilesMatch>

<IfModule mod_headers.c>
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), camera=(self), microphone=(self)"
    # CSP e HSTS são emitidos pelo PHP (Resposta.php) para poder variar por rota/ambiente.
</IfModule>

# Sem cache para HTML; assets versionados por query ?v= (hash) com cache longo
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 30 days"
    ExpiresByType application/javascript "access plus 30 days"
    ExpiresByType image/png "access plus 30 days"
    ExpiresByType image/webp "access plus 30 days"
</IfModule>
```

Adicionalmente, cada pasta interna (`app/`, `config/`, `database/`, `storage/`) recebe um
`.htaccess` próprio com `Require all denied` — proteção que sobrevive mesmo se o `.htaccess`
da raiz for sobrescrito num deploy por FTP.

**Por que funciona em subpasta:** a regra `RewriteRule ^ index.php` usa substituição
*relativa ao diretório* do `.htaccess`, então o Apache reescreve para
`/diariobebe/index.php` na subpasta e para `/index.php` no domínio próprio, sem editar nada.
No PHP, `BASE_PATH` é **autodetectado** de `dirname($_SERVER['SCRIPT_NAME'])`, com override
opcional `BASE_PATH=` no `.env` — atende o requisito "sem alterar uma linha de código".

### 3.2 Tabela de rotas

Rotas de navegação exatamente como no briefing (seção 2.1), confirmadas. Complementos:

| Rota | Método | Nome | Papel / observação |
|---|---|---|---|
| `/manifest.webmanifest` | GET | `pwa.manifest` | gerado por PHP com `BASE_PATH` (§4.3) |
| `/sw.js` | GET | `pwa.sw` | idem; `Service-Worker-Allowed: BASE_PATH` |
| `/foto/{codigo}` | GET | `foto.ver` | autenticado; checa família antes de servir |
| `/download/{codigo}` | GET | `download.baixar` | responsável/admin; via `arquivos_gerados` |
| `/termos/{tipo}` | GET | `lgpd.termo` | autenticado (aceite no primeiro login) |
| `/solicitacoes/{codigo}` | GET/POST | `solicitacoes.decidir` | responsável aprova/recusa |
| `/api/registros` | POST | `api.registros.criar` | cuidador; idempotente por `uuid_cliente` |
| `/api/sincronizar` | POST | `api.sincronizar` | lote da fila offline |
| `/api/dia/{data}` | GET | `api.dia` | estado da grade (polling dos pais, 60 s) |
| `/api/webhook/status` | POST | `api.webhook.status` | n8n confirma entrega (X-Api-Key) |
| `/api/tarefas/{tarefa}` | POST | `api.tarefas` | cron web/n8n: `omissao`, `resumo`, `fila` (token) |
| `/install/migrate.php` | GET/POST | — | fora do roteador; token próprio no `.env` |

Convenções do roteador: minúsculas, sem barra final, acentos→slug; forma divergente responde
`301` para a canônica; 404/403 renderizados dentro do layout; `url('nome.rota', [...])` é a
única forma de gerar link (o mesmo mapa é exposto ao JS via `window.APP.rotas` para o helper
`urlJs()` — nenhuma URL literal nem no PHP nem no JavaScript).

---

## 4. Pontos ambíguos ou arriscados (com decisão proposta)

**4.1 – "`.env` fora do webroot" é impossível em subpasta de `public_html`.**
Em `itthrive.com.br/diariobebe`, tudo está dentro do webroot do domínio. Proposta: o
`Ambiente.php` procura o `.env` primeiro em `../../diariobebe_privado/.env` (isto é, uma pasta
irmã de `public_html`, realmente fora do webroot — recomendado na HostGator) e, se não achar,
usa `./.env` bloqueado por `.htaccess` (dupla proteção). O mesmo vale para `storage/`
(caminho configurável `STORAGE_PATH=` no `.env`). Documentado no guia de deploy.

**4.2 – `RewriteBase /diariobebe/` fixo contradiz "funcionar sem alterar uma linha".**
O briefing pede `RewriteBase` fixo E portabilidade total. São incompatíveis. Proposta adotada:
**sem** `RewriteBase` (substituição relativa, ver §3.1) + `BASE_PATH` autodetectado com
override no `.env`. Nenhuma edição em código ou `.htaccess` ao migrar de subpasta para domínio.

**4.3 – Service worker e manifest em subpasta.** O escopo do SW é limitado pelo caminho do
arquivo. Proposta: servir `/sw.js` e `/manifest.webmanifest` por rota PHP na raiz do
`BASE_PATH`, injetando o caminho base e a lista de assets a cachear; header
`Service-Worker-Allowed`. O `start_url` e `scope` do manifest são gerados com `BASE_PATH`.

**4.4 – E-mail único global impede a mesma babá em duas famílias.** O modelo
`usuarios.familia_id` + `email UNIQUE` fixa 1 usuário = 1 família. Proposta v1: manter como
está (simples, atende o caso familiar); registrar como evolução v2 a tabela-ponte
`usuario_familias` caso a comercialização exija cuidadora em múltiplas famílias. Se preferir
já suportar isso na v1, o modelo de autenticação muda — decidir antes da Fase 1.

**4.5 – "Cron" em hospedagem compartilhada.** Sem processo residente. Proposta: endpoint
`/api/tarefas/{tarefa}` protegido por token, chamado pelo **cron do cPanel** (curl) a cada
5 min e/ou pelo scheduler do n8n — os dois funcionam; o endpoint é idempotente. Alerta de
omissão, resumo diário e reenvio da fila rodam por aí. Precisão prática: ±5 minutos.

**4.6 – PDF sem Composer.** Proposta: Fase 5/6 usam **FPDF vendorizado** (arquivo único, sem
dependências, roda em shared hosting) para resumo diário e Modo Pediatra; toda tela de
relatório também tem versão imprimível (CSS `@media print`) como plano B imediato.

**4.7 – Canal "push" do resumo diário.** Web Push exige chaves VAPID e endpoint próprio;
em Android via PWA funciona, mas é o canal de maior risco técnico. Proposta: v1 entrega
WhatsApp + e-mail (via n8n); "push" fica visível na configuração como "em breve" e entra
após a Fase 5. A fila (`fila_notificacoes.canal`) já prevê `push`.

**4.8 – Geolocalização da babá.** Citada só no termo LGPD ("se ativada"), sem requisito
funcional. Proposta: **não coletar** na v1; o termo do cuidador menciona apenas horários e
registros de atividade. Evita coleta de dado sensível sem uso.

**4.9 – Turno automático (regra 8.8).** "Entrada = primeiro registro do dia" é imediato;
"saída = último registro" só é conhecível depois. Proposta: entrada abre no primeiro registro
do dia; a saída é consolidada pelo job de fim de janela (ou pelo próximo dia), sempre
ajustável manualmente com auditoria (`entrada_manual` + `registro_versoes` não se aplica;
ajustes de turno vão para `log_acessos` com ação `turno_ajustado`).

**4.10 – Formatos de foto.** Câmeras Android/iPhone geram HEIC, que o GD da HostGator não lê.
Proposta: aceitar JPEG/PNG/WebP (validação por MIME real com `finfo` + reencodificação via GD,
que também remove EXIF/geotag por padrão — bom para LGPD); limite 8 MB; thumb 480 px.

**4.11 – Grade com blocos flexíveis + roteiro.** Quando a granularidade é "flexível" e há
roteiro, a linha do dia é o **bloco do roteiro** (hora_inicio–hora_fim) e registros avulsos
aparecem intercalados por horário. Estados: âmbar = bloco obrigatório sem registro há mais de
15 min do fim; vermelho = bloco encerrado sem registro. Os limiares ficam em configuração
(`tolerancia_atraso_minutos`, padrão 15). Confirmar se essa leitura do "atrasado" é a esperada.

**4.12 – Múltiplas crianças.** Toda tela operacional (Meu Dia, Acompanhar) tem seletor de
criança persistido na sessão; ações rápidas registram para a criança selecionada. Roteiro,
resumo e relatórios são sempre por criança.

---

## 5. Próximo passo

Aguardando aprovação deste planejamento para iniciar a **Fase 1 — Fundação**
(estrutura, front controller, roteador, autenticação, migrações, seed de categorias,
multi-tenant, layout base, 404/403).
