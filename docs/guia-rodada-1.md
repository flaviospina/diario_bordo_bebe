# Guia de atualização — Rodada 1 (versão 0.5.0)

> **100% por cliques** — nada de terminal ou comandos.
> Você vai usar: o **GitHub** (baixar o código), o **FileZilla** (subir os
> arquivos), o **cPanel** (e-mail e `.env`) e o **navegador** (migração e testes).
>
> **Siga na ordem, sem pular etapas.** Cada parte termina com uma
> **✅ Verificação** — só avance quando ela passar.
> Tempo total: 25–35 minutos.

O que esta rodada instala:
1. **Registro multi-atividade** — a babá marca várias atividades (mamadeira + fralda + soneca…) e salva tudo num só horário;
2. **E-mail automático de convite** — o botão "Gerar convite" na lista de espera envia o e-mail sozinho, com relatório de envios no painel;
3. **Novidades** — página pública `/novidades` + e-mail macro aos responsáveis.

---

## PARTE 0 — Baixar o código atualizado (GitHub)

1. Abra o repositório no navegador: `github.com/flaviospina/diario_bordo_bebe`.
2. Logo acima da lista de arquivos há um seletor de **branch** (botão cinza,
   à esquerda). Clique nele e escolha **`claude/diario-bebe-webapp-4m1syg`**.
   ⚠ Este passo é essencial — o branch `main` NÃO tem as atualizações.
3. Clique no botão verde **`<> Code`** → **Download ZIP**.
4. O arquivo baixado se chama algo como
   `diario_bordo_bebe-claude-diario-bebe-webapp-4m1syg.zip`.
   Clique nele com o botão direito → **Extrair tudo** → Extrair.
5. Abra a pasta extraída: dentro dela deve haver `app`, `assets`, `config`,
   `database`, `install`, `index.php` etc. Deixe essa janela aberta.

**✅ Verificação:** dentro da pasta extraída existe o arquivo
`app/Views/registro/criar_varios.php` e a pasta `assets/js` tem o `modal.js`.
Se sim, você baixou a versão certa.

---

## PARTE A — Subir os arquivos (FileZilla)

1. Abra o **FileZilla** e conecte:
   - **Host:** `ftp.itthrive.com.br` · **Usuário/Senha:** os do seu FTP
     na HostGator · **Porta:** deixe vazia → botão **Conexão rápida**.
2. **Lado ESQUERDO** (seu computador): navegue até a pasta extraída na
   Parte 0 (a que contém `app`, `assets`, `config`…).
3. **Lado DIREITO** (servidor): navegue até **`/public_html/diariobebe`**
   (dê dois cliques nas pastas até chegar lá — você deve ver as mesmas
   pastas `app`, `assets`, `config`… do lado direito).
4. No lado esquerdo, selecione **estes 5 itens** (clique no primeiro,
   segure `Ctrl` e clique nos outros):
   - pasta **`app`**
   - pasta **`assets`**
   - pasta **`config`**
   - pasta **`database`**
   - pasta **`install`**
5. **Arraste os 5 para o lado direito** (para dentro de `/public_html/diariobebe`).
6. Vai aparecer a janela **"O arquivo de destino já existe"**:
   marque **"Sobrescrever"** + a caixinha **"Sempre usar esta ação"**
   (e "Aplicar somente à fila atual") → **OK**.
7. Aguarde a fila terminar: lá embaixo, a aba **"Transferências com falha"
   deve ficar com 0**. Se tiver falhas: clique com o botão direito na lista
   de falhas → **Restaurar e recolocar todos os arquivos na fila** →
   botão direito na fila → **Processar fila**.

> ⚠ **NÃO** envie: o arquivo `.env` não está no ZIP (ótimo — o do servidor
> fica intacto) e as pastas `docs/` e `scripts/` não precisam subir.
> ⚠ **NÃO apague nada** no servidor — só arraste por cima.

**✅ Verificação:** abra no navegador
`https://itthrive.com.br/diariobebe/novidades`
Deve abrir a página **"Novidades"** (mesmo dizendo que não há novidades).
Se der "Ops!" ou "Página não encontrada" → alguma pasta não subiu inteira;
repita os passos 4–7.

---

## PARTE B — Rodar a migração 0010 (navegador)

1. Abra (troque `SEU_TOKEN` pelo valor de `MIGRATE_TOKEN` do seu `.env` —
   a Parte C mostra como abrir o `.env` para consultar):

   `https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_TOKEN`

2. A página deve mostrar em verde:
   **"Migração executada: 0010_multiatividade_emails_novidades.php"**
   *(se mostrar "Nenhuma migração pendente", também está ok — já rodou antes).*

**✅ Verificação:** recarregue a mesma página. Agora deve dizer
**"Nenhuma migração pendente"**.

---

## PARTE C — Configurar o e-mail (cPanel)

### C.1 Criar a conta de e-mail
1. Entre no **cPanel** (portal da HostGator → seu plano → cPanel).
2. Na busca do topo, digite **"contas"** → abra **Contas de E-mail**
   (*Email Accounts*).
3. Botão **+ Criar** → Nome de usuário: **`no-reply`** → domínio
   `itthrive.com.br` → defina uma **senha forte e anote-a** → **Criar**.

### C.2 Editar o `.env` (onde moram os tokens)
1. No cPanel, abra o **Gerenciador de Arquivos** (*File Manager*).
2. Canto superior direito → **Configurações** → marque
   **"Mostrar arquivos ocultos (dotfiles)"** → **Salvar**.
3. Encontre o arquivo **`.env`** — procure primeiro em
   `diariobebe_privado/` (pasta irmã de `public_html`); se não estiver,
   em `public_html/diariobebe/`.
4. Clique nele com o botão direito → **Edit** → **Edit** de novo.
   *(Aproveite e anote o valor de `MIGRATE_TOKEN` para a Parte B.)*
5. Adicione estas linhas no final (troque só a senha):

```
EMAIL_REMETENTE=no-reply@itthrive.com.br
EMAIL_NOME_REMETENTE="Diário do Bebê"
SMTP_HOST=mail.itthrive.com.br
SMTP_PORTA=465
SMTP_SEGURANCA=ssl
SMTP_USUARIO=no-reply@itthrive.com.br
SMTP_SENHA=cole_aqui_a_senha_criada_no_C1
```

6. Botão **Save Changes** (canto superior direito) → **Close**.

### C.3 SPF/DKIM (para não cair em spam)
1. No cPanel, busque **"deliver"** → abra **Email Deliverability**
   (*Capacidade de entrega de e-mail*).
2. Na linha do `itthrive.com.br`, clique **Gerenciar** (*Manage*).
3. Se DKIM ou SPF estiverem com ⚠, clique **Reparar** (*Repair*)
   e aguarde ~5 minutos até ficar **Valid ✅**.

**✅ Verificação:** volte à tela Email Deliverability — o domínio deve
estar **sem alertas**. (Se essa tela não existir no seu cPanel, me avise
que eu te passo os registros para colar no Zone Editor.)

---

## PARTE D — Limpar o cache do celular ⭐ NÃO PULE

O app guarda os arquivos antigos no aparelho (é assim que funciona
offline). Depois de **toda** atualização, em **cada** aparelho:

1. Feche o app **de verdade**: arraste de baixo para cima e segure (ou
   botão de apps recentes) → **descarte o cartão** do Diário do Bebê.
2. Abra o app de novo.
3. Confira: role até o **rodapé** de qualquer tela logada — deve mostrar
   **"Diário do Bebê · v0.5.0"**.
4. Se ainda mostrar 0.4.x: aguarde ~1 minuto, feche e abra mais uma vez
   (o aparelho troca os arquivos em segundo plano na primeira abertura).

**✅ Verificação:** rodapé com **v0.5.0**. Sem isso, os testes abaixo
vão falhar — não siga adiante até ver 0.5.0.

---

## PARTE E — Testar cada funcionalidade

### E.1 Multi-atividade (2 min)
1. Entre no app → **Meu Dia** → botão redondo laranja **+**.
2. Ative a chavinha **"Aconteceu mais de uma coisa? Selecionar várias"**.
3. Toque em **Mamadeira**, depois **Fralda**, depois **Soneca**.
   → As três devem ficar **marcadas** (borda verde, fundo sálvia),
   **sem** abrir outra tela.
4. Toque no botão **"Registrar 3 atividades juntas"** que apareceu embaixo.
5. Preencha o pouco que cada uma pede (volume, conteúdo da fralda) → **Salvar**.
6. **Resultado esperado:** mensagem *"3 atividades registradas juntas: …"*
   e os três itens no mesmo horário do Meu Dia.

### E.2 Novidades (3 min)
1. Saia e entre com a conta **super_admin** → **Painel** → cartão
   **"Novidades (comunicados às famílias)"**.
2. Copie e cole a sugestão abaixo → **Publicar novidade**.
3. Abra `https://itthrive.com.br/diariobebe/novidades` → ela está lá.
4. No painel, linha da novidade → **"Enviar e-mail"**.
5. **Resultado esperado:** *"Novidade enviada: N e-mail(s)"*, linhas
   **enviado** no cartão "E-mails enviados", e o e-mail na sua caixa.

> **Título:** Registre várias atividades de uma vez
> **Resumo:** Agora dá para marcar mamadeira, fralda e soneca juntas, num só registro de horário.
> **Detalhes:**
> Na janela de registro do Meu Dia, ative a chavinha "Selecionar várias" e toque em tudo o que aconteceu naquele horário.
>
> Uma tela única mostra os campos de cada atividade — preencha e salve de uma vez. Na linha do tempo, elas aparecem agrupadas no mesmo horário.
>
> Para foto ou horário de fim, registre a atividade sozinha, como antes.

### E.3 Convite por e-mail (3 min)
1. Numa **janela anônima**, abra `https://itthrive.com.br/diariobebe`
   → formulário **"Quero um convite"** → preencha com **um e-mail seu de
   teste** → envie.
2. Na conta super_admin → **Painel** → **Lista de espera** → o pedido
   está lá → botão **"Gerar convite"**.
3. **Resultado esperado:** *"Convite enviado por e-mail para …"* e o
   e-mail bonito na caixa de entrada (botão "Criar a nossa família",
   3 passos e link do tutorial). Caiu no spam? → refaça a Parte C.3.

---

## PARTE F — Se algo falhar (por sintoma)

| Sintoma | Causa provável | Solução |
|---|---|---|
| Chavinha aparece, mas cada toque abre o formulário de UMA atividade | arquivo `modal.js` antigo em cache no aparelho | Parte D (fechar de verdade e reabrir); rodapé precisa mostrar v0.5.0 |
| Chavinha nem aparece | pasta `app` não subiu inteira | Parte A, passos 4–7 |
| Rodapé insiste em 0.4.x | pasta `config` não subiu | Parte A; depois Parte D |
| Botão "Registrar N atividades" dá "Ops!" | `config` ou `app` desatualizados no servidor | Parte A (as 5 pastas de novo) |
| Salvar as atividades dá erro | migração 0010 não rodou | Parte B |
| Relatório mostra e-mail **falhou** | dados SMTP errados no `.env` | Parte C.2 — o erro exato aparece na própria linha do relatório |
| E-mail chega no spam | SPF/DKIM pendentes | Parte C.3 |
| `/novidades` dá 404 | pasta `config` (rotas) não subiu | Parte A |

Qualquer sintoma fora da tabela: me diga **qual parte** você estava
executando e **a mensagem exata** da tela — eu resolvo do meu lado.
