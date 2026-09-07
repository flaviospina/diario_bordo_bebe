# Guia de atualização — Rodada 1 (versão 0.5.0)

> **Siga na ordem, sem pular etapas.** Cada parte termina com uma
> **✅ Verificação** — só avance quando ela passar.
> Tempo total estimado: 20–30 minutos.

O que esta rodada instala:
1. **Registro multi-atividade** — a babá marca várias atividades e salva tudo num só horário;
2. **E-mail automático de convite** — "Gerar convite" na lista de espera envia o e-mail sozinho, com relatório de envios no painel;
3. **Novidades** — página pública `/novidades` + e-mail macro aos responsáveis.

---

## PARTE A — Subir os arquivos (FTP)

### Opção 1 (RECOMENDADA): subir o projeto inteiro

É a forma que **nunca** deixa arquivo para trás. O script exclui sozinho
`.env`, `.git`, `docs/` e `scripts/` — não sobrescreve nada sensível.

No seu computador, na pasta do projeto:

```bat
:: Windows (Prompt de Comando)
set DIARIOBEBE_FTP_HOST=ftp.itthrive.com.br
set DIARIOBEBE_FTP_USER=seu_usuario_ftp
set DIARIOBEBE_FTP_PASS=sua_senha_ftp
python scripts\deploy_ftp.py
```

```bash
# Mac/Linux (Terminal)
export DIARIOBEBE_FTP_HOST=ftp.itthrive.com.br
export DIARIOBEBE_FTP_USER=seu_usuario_ftp
export DIARIOBEBE_FTP_PASS=sua_senha_ftp
python3 scripts/deploy_ftp.py
```

*Sem o script?* No FileZilla, arraste as pastas **`app/`, `assets/`,
`config/`, `database/`, `install/`** e o arquivo **`index.php`** inteiros
para `/public_html/diariobebe/`, escolhendo **"Sobrescrever"** para tudo.

### Opção 2: subir só os 19 arquivos da rodada

Use apenas se preferir o mínimo. **Todos** precisam ir — o sintoma
"a chavinha aparece mas só marca uma opção" é exatamente um destes
faltando (em geral `assets/js/modal.js` ou `config/app.php`):

| # | Arquivo | Para quê |
|---|---------|----------|
| 1 | `assets/js/modal.js` | ⭐ a lógica da seleção múltipla |
| 2 | `assets/css/app.css` | ⭐ o visual da seleção e da barra |
| 3 | `config/app.php` | ⭐ versão 0.5.0 (força o celular a baixar os JS/CSS novos) |
| 4 | `app/Views/cuidador/dia.php` | a chavinha "Selecionar várias" |
| 5 | `app/Views/registro/criar_varios.php` | *(novo)* o formulário empilhado |
| 6 | `app/Views/registro/_campos.php` | campos por categoria no multi |
| 7 | `app/Controllers/RegistroController.php` | salvar as várias atividades |
| 8 | `app/Repositories/RepositorioRegistros.php` | coluna grupo_registro |
| 9 | `config/rotas.php` | rotas `/registrar-varios` e `/novidades` |
| 10 | `app/Core/ClienteSmtp.php` | *(novo)* envio SMTP |
| 11 | `app/Services/ServicoEmail.php` | template de e-mail + relatório |
| 12 | `app/Repositories/RepositorioEmails.php` | *(novo)* log de envios |
| 13 | `app/Repositories/RepositorioListaEspera.php` | buscar interessado |
| 14 | `app/Repositories/RepositorioNovidades.php` | *(novo)* novidades |
| 15 | `app/Controllers/PainelAdminController.php` | painel: e-mails + novidades |
| 16 | `app/Controllers/HomeController.php` | página /novidades |
| 17 | `app/Views/painel/index.php` | cartões novos do painel |
| 18 | `app/Views/publico/novidades.php` | *(novo)* página pública |
| 19 | `database/migrations/0010_multiatividade_emails_novidades.php` | *(novo)* migração |

**✅ Verificação da Parte A:** abra
`https://itthrive.com.br/diariobebe/novidades` no navegador.
Se abrir a página "Novidades" (mesmo vazia), os arquivos subiram.
Se der "Ops!" ou 404 → releia a Opção 1 e suba o projeto inteiro.

---

## PARTE B — Rodar a migração 0010

1. Abra: `https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
   *(o token está no seu `.env` — arquivo oculto; no Gerenciador de
   Arquivos do cPanel: Configurações → "Mostrar arquivos ocultos").*
2. A página deve listar:
   `Migração executada: 0010_multiatividade_emails_novidades.php`
   *(ou "Nenhuma migração pendente", se você já rodou antes — também está ok).*

**✅ Verificação da Parte B:** rode a URL de novo. Deve dizer
**"Nenhuma migração pendente"**. Isso confirma que a 0010 está aplicada.

---

## PARTE C — Configurar o e-mail no `.env`

1. **Crie a conta de e-mail** (se ainda não existe):
   cPanel → **Contas de E-mail** → Criar → `no-reply` @ `itthrive.com.br`
   → defina uma senha forte e **anote-a**.
2. **Edite o `.env`** (o mesmo arquivo dos tokens) e acrescente no final:

```
EMAIL_REMETENTE=no-reply@itthrive.com.br
EMAIL_NOME_REMETENTE="Diário do Bebê"
SMTP_HOST=mail.itthrive.com.br
SMTP_PORTA=465
SMTP_SEGURANCA=ssl
SMTP_USUARIO=no-reply@itthrive.com.br
SMTP_SENHA=cole_aqui_a_senha_da_conta_no-reply
```

3. **SPF/DKIM**: cPanel → busque **"deliver"** ("Email Deliverability" /
   "Capacidade de entrega de e-mail") → `itthrive.com.br` → **Gerenciar** →
   se houver ⚠, clique **Reparar** e aguarde ficar "Valid" ✅.

**✅ Verificação da Parte C:** nenhuma ainda — o teste real é na Parte E.3.

---

## PARTE D — Limpar o cache do celular ⭐ NÃO PULE

O app é um PWA: o celular guarda os JS/CSS antigos. Depois de toda
atualização, faça **uma vez em cada aparelho**:

- **Instalado na tela inicial:** feche o app DE VERDADE (aparelhos
  recentes: arraste para cima e descarte o cartão do app) → abra de novo →
  puxe a tela para baixo para recarregar.
- **No navegador:** feche a aba e abra de novo, ou use janela anônima.
- **Conferência:** role até o rodapé de qualquer tela logada — deve
  mostrar **"Diário do Bebê · v0.5.0"**.

**✅ Verificação da Parte D:** rodapé com **v0.5.0**.
Se mostrar 0.4.x → o `config/app.php` não subiu (volte à Parte A) ou o
app não foi fechado de verdade.

---

## PARTE E — Testar cada funcionalidade

### E.1 Multi-atividade (2 min)
1. Entre como **babá** (ou sua conta) → **Meu Dia** → botão laranja **+**.
2. Ative a chavinha **"Aconteceu mais de uma coisa? Selecionar várias"**.
3. Toque em **Mamadeira**, **Fralda** e **Soneca** → as três devem ficar
   com **borda verde e fundo sálvia** (não navegar!).
4. Toque em **"Registrar 3 atividades juntas"** (barra que aparece embaixo).
5. Preencha: volume da mamadeira, conteúdo da fralda → **Salvar**.
6. **Resultado esperado:** mensagem "3 atividades registradas juntas: …"
   e os três itens no mesmo horário do Meu Dia.

### E.2 Novidades (3 min)
1. Entre com a conta **super_admin** → **Painel** → cartão
   **"Novidades (comunicados às famílias)"**.
2. Preencha e clique **Publicar novidade** (sugestão pronta abaixo*).
3. Abra `https://itthrive.com.br/diariobebe/novidades` → a novidade está lá.
4. De volta ao painel, clique **"Enviar e-mail"** na linha da novidade.
5. **Resultado esperado:** "Novidade enviada: N e-mail(s)" e as linhas no
   cartão **"E-mails enviados"** como *enviado*. Confira sua caixa de entrada.

> \* Sugestão de primeira novidade —
> **Título:** Registre várias atividades de uma vez ·
> **Resumo:** Agora dá para marcar mamadeira, fralda e soneca juntas, num só registro de horário. ·
> **Detalhes:** Na janela de registro do Meu Dia, ative a chavinha "Selecionar várias" e toque em tudo o que aconteceu naquele horário. (linha em branco) Uma tela única mostra os campos de cada atividade — preencha e salve de uma vez. (linha em branco) Para foto ou horário de fim, registre a atividade sozinha, como antes.

### E.3 Convite por e-mail (3 min)
1. Na janela anônima, abra a landing → **"Quero um convite"** → preencha
   com **um e-mail seu de teste** → envie.
2. No **Painel** → **Lista de espera** → o pedido aparece → **"Gerar convite"**.
3. **Resultado esperado:** "Convite enviado por e-mail para …" e o e-mail
   bonito na sua caixa (botão "Criar a nossa família" + 3 passos + tutorial).
   Se cair no spam → volte à Parte C, passo 3 (SPF/DKIM).

---

## PARTE F — Se algo falhar (por sintoma)

| Sintoma | Causa | Solução |
|---|---|---|
| Chavinha aparece, mas cada toque abre o formulário de uma atividade só | `modal.js` antigo em cache | Parte D (fechar e reabrir o app); confira v0.5.0 no rodapé |
| Chavinha nem aparece | `app/Views/cuidador/dia.php` não subiu | Parte A |
| Rodapé mostra 0.4.x | `config/app.php` não subiu | Parte A + Parte D |
| Botão "Registrar N atividades" dá "Ops!" | `config/rotas.php` ou `RegistroController.php` antigos | Parte A (projeto inteiro) |
| Salvar multi dá erro de banco | migração 0010 não rodou | Parte B |
| "Enviar e-mail" marca *falhou* no relatório | SMTP errado no `.env` | Parte C; o erro exato aparece no cartão E-mails enviados |
| E-mail chega no spam | SPF/DKIM pendentes | Parte C, passo 3 |
