# Deploy na HostGator — itthrive.com.br/diariobebe

Guia passo a passo para colocar o Diário do Bebê no ar em hospedagem
compartilhada HostGator, sem terminal no servidor: upload por FTP/File Manager
e migrações do banco pelo navegador.

---

## 0. O que você vai precisar

- Acesso ao **cPanel** da conta HostGator de `itthrive.com.br`.
- O código do projeto (passo 1).
- 10–15 minutos.

Ordem geral: código no lugar → PHP 8.2+ → banco criado no cPanel → `.env`
preenchido → `install/migrate.php` no navegador (cria TODAS as tabelas e as
categorias, e cadastra a primeira família) → login.

---

## 1. Baixar o código

Baixe o ZIP da branch no GitHub:

```
https://github.com/flaviospina/diario_bordo_bebe/archive/refs/heads/claude/diario-bebe-webapp-4m1syg.zip
```

(Ou, depois do merge: `Code → Download ZIP` na página do repositório.)

Descompacte localmente. A pasta extraída (`diario_bordo_bebe-...`) contém
`index.php`, `.htaccess`, `app/`, `config/`, `database/`, `assets/`,
`install/`, `storage/` — é **o conteúdo dela** que vai para o servidor,
não a pasta com nome do zip.

> **Atenção aos arquivos ocultos:** `.htaccess` (na raiz e dentro de `app/`,
> `config/`, `database/`, `storage/`, `docs/`) é essencial. No Windows/macOS
> eles podem não aparecer no Explorer/Finder, mas estão no zip. O método do
> passo 3-A (upload do zip + Extract no servidor) preserva todos.

---

## 2. Conferir o PHP e o document root

1. **cPanel → Software → MultiPHP Manager**: selecione o domínio
   `itthrive.com.br` e defina **PHP 8.2** ou **8.3**. (As extensões
   necessárias — `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `json` — já vêm
   habilitadas por padrão na HostGator; se precisar, confira em
   *MultiPHP INI Editor* / *Select PHP Extensions*.)
2. **cPanel → Domains**: veja o **Document Root** de `itthrive.com.br`:
   - domínio principal → normalmente `public_html/`;
   - addon domain → normalmente `public_html/itthrive.com.br/`.

   O destino do sistema é `DOCUMENT_ROOT/diariobebe/`. Nos exemplos abaixo
   uso `public_html/diariobebe/` — ajuste se o seu document root for outro.

---

## 3. Subir os arquivos

### Opção A — cPanel File Manager (recomendada, preserva os ocultos)

1. Compacte **o conteúdo** da pasta do projeto num zip (o `index.php` deve
   estar na raiz do zip, não dentro de uma subpasta). Pode usar o próprio zip
   do GitHub, mas aí o Extract criará a pasta `diario_bordo_bebe-claude-...`
   e você precisará mover/renomear (o File Manager tem *Move* e *Rename*).
2. **cPanel → Files → File Manager**. No canto superior direito, clique em
   **Settings** e marque **Show Hidden Files (dotfiles)** — sem isso você não
   enxerga os `.htaccess`.
3. Navegue até o document root, crie a pasta **`diariobebe`** e entre nela.
4. **Upload** → envie o zip → volte → selecione o zip → **Extract**.
5. Confira que `index.php` e `.htaccess` estão direto em
   `.../diariobebe/` (e não em `.../diariobebe/alguma-subpasta/`).
   Apague o zip.

### Opção B — FTP com FileZilla

1. **cPanel → Files → FTP Accounts**: use a conta principal ou crie uma
   dedicada. Host: `ftp.itthrive.com.br` (ou o hostname do servidor, ex.:
   `gatorXXXX.hostgator.com` — está no e-mail de boas-vindas), porta 21,
   criptografia "FTPS explícito".
2. No FileZilla: *Servidor → Forçar exibição de arquivos ocultos*.
3. Arraste todo o conteúdo do projeto para `public_html/diariobebe/`.

### Opção C — script de deploy (para as próximas atualizações)

No seu computador (com Python 3):

```bash
export DIARIOBEBE_FTP_HOST="ftp.itthrive.com.br"
export DIARIOBEBE_FTP_USER="seu_usuario_ftp"
export DIARIOBEBE_FTP_PASS="sua_senha_ftp"
export DIARIOBEBE_FTP_DIR="/public_html/diariobebe"   # ajuste ao document root
python3 scripts/deploy_ftp.py
```

O script sobe a árvore inteira via FTPS (incluindo `.htaccess`), e **nunca**
envia `.env`, `.git/`, `scripts/` nem `docs/`. Rode de novo a cada
atualização — ele sobrescreve o que mudou.

---

## 4. Criar o banco de dados no cPanel

**cPanel → Databases → MySQL® Databases** (ou o *MySQL Database Wizard*, que
faz os 3 passos em sequência):

1. **Create New Database**: nome `diariobebe` → o cPanel cria como
   `USUARIO_diariobebe` (ele prefixa com o usuário da conta — anote o nome
   completo).
2. **MySQL Users → Add New User**: usuário `diariobebe` (vira
   `USUARIO_diariobebe`) e uma senha forte (use o botão *Password Generator*
   e guarde a senha).
3. **Add User To Database**: selecione o usuário e o banco → **Add** →
   marque **ALL PRIVILEGES** → *Make Changes*.

Não é preciso criar nenhuma tabela manualmente — as 20 tabelas, índices,
chaves estrangeiras e as 48 categorias globais são criadas pelo script de
migração no passo 6.

---

## 5. Criar o `.env` (fora do webroot)

1. No File Manager, vá para a **home da conta** (um nível ACIMA de
   `public_html` — ex.: `/home/SEU_USUARIO/`) e crie a pasta
   **`diariobebe_privado`**. O sistema procura o `.env` aí automaticamente,
   tanto com domínio principal quanto com addon domain.
2. Dentro dela, **+ File** → crie `.env` → *Edit* e cole o conteúdo abaixo,
   preenchendo com os seus dados:

```ini
APP_AMBIENTE=producao
APP_URL=https://itthrive.com.br/diariobebe
APP_FUSO=America/Sao_Paulo

BD_HOST=localhost
BD_PORTA=3306
BD_NOME=USUARIO_diariobebe
BD_USUARIO=USUARIO_diariobebe
BD_SENHA=senha_gerada_no_passo_4

# deixe vazio para usar ./storage; ver "Storage fora do webroot" abaixo
STORAGE_PATH=

# tokens: 40+ caracteres aleatórios cada (use o Password Generator do cPanel
# duas vezes e cole; sem espaços)
MIGRATE_TOKEN=cole_aqui_um_token_longo_e_aleatorio
TAREFAS_TOKEN=cole_aqui_outro_token_longo_e_aleatorio
APP_CHAVE_SECRETA=cole_aqui_um_terceiro_valor_aleatorio

N8N_WEBHOOK_URL=
N8N_API_KEY=

EMAIL_REMETENTE=nao-responda@itthrive.com.br
EMAIL_NOME_REMETENTE="Diário do Bebê"
```

- `BASE_PATH` não precisa ser definido — é autodetectado (`/diariobebe`).
- **Alternativa** (menos ideal): colocar o `.env` na raiz de
  `public_html/diariobebe/` — funciona e o `.htaccess` bloqueia o acesso
  web, mas a pasta privada é a opção mais segura.

**Storage fora do webroot (recomendado):** ainda em `diariobebe_privado`,
crie a pasta `storage` e, dentro dela, `fotos`, `thumbs`, `pdfs`,
`exportacoes`, `logs`, `cache`. Depois aponte no `.env`:
`STORAGE_PATH=/home/SEU_USUARIO/diariobebe_privado/storage`
(o caminho completo aparece no topo do File Manager). Se deixar vazio, o
sistema usa `public_html/diariobebe/storage/`, que já vem bloqueado por
`.htaccess`.

---

## 6. Criar as tabelas (migrações pelo navegador)

Abra no navegador, substituindo pelo seu `MIGRATE_TOKEN`:

```
https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN
```

Você deve ver, em verde:

- `Migração executada: 0001_nucleo_multi_tenant.php ...` até `0007_...`
  (7 no total — criam as 20 tabelas com índices e FKs);
- `Seed de categorias: 48 categoria(s) global(is) inserida(s).`

Na mesma tela aparece o formulário **"Criar família inicial"**: preencha o
nome da família, seu nome, seu e-mail e uma senha (mínimo 10 caracteres) —
isso cria a família e o seu usuário `admin_familia`. Esse formulário só
existe enquanto não há nenhuma família; depois disso, todo mundo entra por
convite.

O script é **idempotente**: pode abrir de novo quantas vezes quiser
("Nenhuma migração pendente") e é ele que você usará nas fases futuras para
aplicar novas migrações.

> Se aparecer "Token ausente ou inválido": o `.env` não foi encontrado ou o
> token da URL não bate com o `MIGRATE_TOKEN`. Se aparecer erro de conexão:
> revise `BD_NOME`, `BD_USUARIO` e `BD_SENHA` (com o prefixo do cPanel!).

---

## 7. Testar

1. `https://itthrive.com.br/diariobebe` → deve redirecionar para
   `/diariobebe/entrar`.
2. Entre com o e-mail e a senha criados no passo 6 → o sistema mostra o
   **termo de consentimento LGPD** → aceite → você cai em `/acompanhar`
   (as telas das próximas fases aparecem como "Em desenvolvimento", já com
   o controle de papéis ativo).
3. Testes de segurança (todos devem ser bloqueados):
   - `https://itthrive.com.br/diariobebe/app/` → **403**
   - `https://itthrive.com.br/diariobebe/config/` → **403**
   - `https://itthrive.com.br/diariobebe/.env` → **403** (se você usou o
     fallback na raiz)
4. `https://itthrive.com.br/diariobebe/qualquer-coisa` → página 404 do
   sistema (se aparecer o 404 do Apache, o `.htaccess` da raiz não subiu).
5. Cadeado HTTPS ativo (se não, **cPanel → Security → SSL/TLS Status** →
   *Run AutoSSL*).

---

## 8. Atualizações futuras

1. Suba os arquivos novos (opção A, B ou C do passo 3 — pode sobrescrever
   tudo; o `.env` não é tocado porque vive em `diariobebe_privado/`).
2. Abra de novo `install/migrate.php?token=...` para aplicar migrações novas.
3. Pronto — dados e configurações são preservados.

---

## Solução de problemas

| Sintoma | Causa provável / correção |
|---|---|
| **500 Internal Server Error** logo de cara | `.htaccess` com diretiva não suportada ou PHP < 8.2. Confirme o MultiPHP em 8.2/8.3. Veja o erro em *cPanel → Metrics → Errors* ou em `storage/logs/php_erros.log`. |
| **404 do Apache em todas as rotas** (ex.: `/entrar`) | O `.htaccess` da raiz não foi enviado (arquivo oculto). Reenvie com "Show Hidden Files" ligado. |
| **"Token ausente ou inválido"** no migrate | `.env` não encontrado (pasta/nome errados) ou token divergente. O `.env` deve chamar exatamente `.env` e estar em `diariobebe_privado/` na home, ou na raiz do projeto. |
| **Erro de conexão com o banco** | `BD_NOME`/`BD_USUARIO` sem o prefixo do cPanel, senha errada, ou usuário sem privilégios no banco (refaça "Add User To Database"). `BD_HOST` na HostGator é `localhost`. |
| **Tela branca** | Erro fatal com display desligado (produção). Consulte `storage/logs/php_erros.log` pelo File Manager. |
| **CSS sem carregar / página "crua"** | A pasta `assets/` não subiu completa; reenvie. |
| **Página antiga após atualizar** | Cache do navegador — Ctrl+F5 ou aba anônima. |
