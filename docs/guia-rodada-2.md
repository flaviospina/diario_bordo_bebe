# Guia de atualização — Rodada 2 (versão 0.7.0)

> **100% por cliques** — nada de terminal ou comandos no seu computador.
> Você vai usar: o **GitHub** (baixar o código), o **FileZilla** (subir os
> arquivos), o **cPanel** (`.env`, phpMyAdmin e cron) e o **navegador**
> (migração e testes). O único site novo é o **console.anthropic.com**,
> onde nasce a chave da IA.
>
> **Siga na ordem, sem pular etapas.** Cada parte termina com uma
> **✅ Verificação** — só avance quando ela passar.
> Tempo total: 30–40 minutos.

O que esta rodada instala:
1. **Acompanhamento com IA** — em Relatórios → Acompanhamento, a IA analisa
   os últimos 14 dias do diário (mamadas, sono, cólicas, fraldas, medicações,
   marcos) e devolve de 2 a 4 observações. Também gera sozinha uma análise
   por semana e avisa os responsáveis por e-mail. Privacidade: só idade e
   sexo vão para a análise — nunca nome, fotos ou textos.
2. **Eventos importantes** — marco de desenvolvimento, vacina, medição
   confirmada e intercorrência agora disparam **e-mail imediato** aos
   responsáveis e ficam guardados.
3. **Ficha do pediatra mais esperta** — a ficha do QR code ganhou a seção
   **"Novidades desde a última consulta"**: o pediatra vê tudo o que
   aconteceu de importante desde a visita anterior.

---

## PARTE 0 — Baixar o código atualizado (GitHub)

1. Abra `github.com/flaviospina/diario_bordo_bebe` no navegador.
2. No seletor de **branch** (botão cinza acima da lista de arquivos),
   escolha **`claude/diario-bebe-webapp-4m1syg`**.
   ⚠ Essencial — o branch `main` NÃO tem as atualizações.
3. Botão verde **`<> Code`** → **Download ZIP**.
4. Botão direito no ZIP baixado → **Extrair tudo** → Extrair.
5. Abra a pasta extraída (dentro dela: `app`, `assets`, `config`,
   `database`, `install`, `index.php`…). Deixe a janela aberta.

**✅ Verificação:** dentro da pasta extraída existem os arquivos
`app/Services/ServicoAcompanhamento.php` e
`database/migrations/0012_acompanhamento_ia_eventos.php`.
Se sim, você baixou a versão certa.

---

## PARTE A — Subir os arquivos (FileZilla)

Igualzinho à Rodada 1:

1. Abra o **FileZilla** → conecte em `ftp.itthrive.com.br` com seu
   usuário/senha de FTP → **Conexão rápida**.
2. **Lado ESQUERDO:** a pasta extraída na Parte 0.
   **Lado DIREITO:** `/public_html/diariobebe`.
3. No lado esquerdo, selecione (com `Ctrl`) **estes 5 itens**:
   pasta **`app`** · pasta **`assets`** · pasta **`config`** ·
   pasta **`database`** · pasta **`install`**.
4. **Arraste os 5 para o lado direito.**
5. Na janela "O arquivo de destino já existe": **Sobrescrever** +
   **"Sempre usar esta ação"** → OK.
6. Espere a fila zerar (aba "Transferências com falha" = 0).

> ⚠ Não apague nada no servidor; só arraste por cima.
> O `.env` do servidor fica intacto (ele não vem no ZIP).

**✅ Verificação:** abra
`https://itthrive.com.br/diariobebe/relatorios/acompanhamento`
(logado como pai/mãe). Deve abrir a página **"Acompanhamento"** com o
botão verde "Gerar a análise de agora". Se der erro de página, alguma
pasta não subiu inteira — repita os passos 3–6.

---

## PARTE B — Rodar a migração 0012 (navegador)

1. Abra (troque `SEU_TOKEN` pelo valor de `MIGRATE_TOKEN` do seu `.env`):

   `https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_TOKEN`

2. Deve aparecer a linha verde:
   **"Migração executada: 0012_acompanhamento_ia_eventos.php — Rodada 2…"**
   (Se aparecer "nenhuma migração pendente", ela já rodou — tudo bem.)

**✅ Verificação:** a página termina com "Sistema instalado" e sem
nenhuma caixa vermelha.

---

## PARTE C — Criar a chave da IA (console.anthropic.com)

> Sem a chave, **nada quebra**: a página de Acompanhamento apenas explica
> que a IA ainda não está ativa. Você pode fazer esta parte depois.

### C.1 Criar a conta e a chave

1. Abra `https://console.anthropic.com` → **Sign up** (pode entrar com a
   conta Google) → confirme o e-mail.
2. No menu, abra **Billing** (ou "Plans & Billing") → **Add credits** →
   adicione **US$ 5** (cartão de crédito internacional). Cada análise
   custa centavos — US$ 5 duram meses com poucas famílias.
3. Menu **API Keys** → botão **Create Key** → nome: `diario-bebe` →
   **Create**. A chave aparece UMA única vez (começa com `sk-ant-`):
   clique em **Copy** e guarde num lugar seguro.

### C.2 Colocar a chave no `.env` (cPanel)

1. cPanel → **Gerenciador de Arquivos** → navegue até a pasta onde está o
   seu `.env` (a mesma da Rodada 1; se não aparecer, botão **Configurações**
   no canto direito → marque **Mostrar arquivos ocultos** → Save).
2. Clique no `.env` → botão **Edit** (Editar) → **Edit** de novo.
3. Adicione (ou complete) estas duas linhas — sem espaços em volta do `=`:

   ```
   ANTHROPIC_API_KEY=sk-ant-SUACHAVEAQUI
   IA_MODELO=claude-opus-5
   ```

4. **Save Changes** → Close.

**✅ Verificação:** abra Relatórios → Acompanhamento no app. O aviso
"A chave da IA ainda não está configurada" deve ter sumido. Toque em
**"Gerar a análise de agora"**: em até ~1 minuto aparecem as observações.

---

## PARTE D — Publicar a novidade para as famílias (phpMyAdmin)

1. cPanel → **phpMyAdmin** → clique no banco do Diário do Bebê (à esquerda).
2. Aba **SQL** → abra no seu computador o arquivo
   `database/dados/novidade_rodada2.sql` (da pasta extraída na Parte 0)
   com o Bloco de Notas → copie TODO o conteúdo → cole → **Executar**.
3. Deve listar a novidade `acompanhamento-com-ia-e-eventos-importantes`.
4. (Opcional) No **Painel** do app (conta super admin), na seção Novidades,
   use **"Enviar e-mail"** para avisar os responsáveis por e-mail.

**✅ Verificação:** saia do app e entre de novo com a sua conta de pai:
o aviso "Novidades no Diário do Bebê 🎉" aparece com a novidade nova.

---

## PARTE E — Análise automática semanal (cron do cPanel)

> Opcional, mas recomendado: é o que faz a análise sair sozinha toda
> semana, com e-mail aos responsáveis. Sem o cron, o botão manual
> continua funcionando normalmente.

1. cPanel → procure **Cron Jobs** (Trabalhos Cron).
2. Em **Add New Cron Job**: no seletor de exemplos (**Common Settings**),
   escolha **Once Per Day (0 0 * * *)** — ou preencha:
   Minute `0` · Hour `8` · Day `*` · Month `*` · Weekday `*`
   (= todo dia às 8h; o sistema só gera de fato 1 análise por criança
   por semana, então rodar todo dia é seguro).
3. No campo **Command**, cole (trocando `SEU_TOKEN` pelo valor de
   `TAREFAS_TOKEN` do seu `.env`):

   ```
   curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/acompanhamento
   ```

4. Botão **Add New Cron Job**.

**✅ Verificação:** a linha aparece na lista "Current Cron Jobs".

---

## PARTE F — Limpar o cache do celular ⭐ NÃO PULE

1. No celular, **feche o app completamente** (arraste para fora da lista
   de apps recentes).
2. Abra de novo e desça até o rodapé: deve mostrar **v0.7.0**.
3. Se ainda mostrar v0.6.0: feche de novo, espere 30 segundos e reabra
   (o app troca de versão sozinho na segunda abertura).

---

## PARTE G — Testar cada funcionalidade

### G.1 Acompanhamento com IA (2 min)
1. Entre como pai/mãe → **Relatórios** → botão **Acompanhamento**.
2. Toque em **"Gerar a análise de agora"** → as observações aparecem em
   cartões (padrão da rotina, pontos para o pediatra, fase, conquistas).
3. Toque de novo no mesmo dia → o app recusa educadamente ("Já existe uma
   análise de hoje") — é o controle de custo funcionando.

### G.2 Eventos importantes por e-mail (3 min)
1. No **Meu Dia**, registre um **Marco de desenvolvimento** (ex.: "Sorriu
   para a vovó").
2. Confira o e-mail dos responsáveis: deve chegar
   **"Marco de desenvolvimento de … — Diário do Bebê"** na hora.
   (No Painel do super admin, o relatório de e-mails também mostra o envio.)
3. O mesmo vale para vacina registrada, medição e intercorrência.

### G.3 Ficha do pediatra (2 min)
1. Ficha do bebê → **"Ficha para consulta"** → gere um link e abra-o em
   **aba anônima** do navegador.
2. Logo depois do cartão "Saúde" deve aparecer
   **"Novidades desde a última consulta"** com o marco do teste G.2.

---

## PARTE H — Se algo falhar (por sintoma)

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| Acompanhamento diz "chave ainda não configurada" | `.env` sem a linha `ANTHROPIC_API_KEY` ou com espaços | Refaça C.2; a linha é `ANTHROPIC_API_KEY=sk-ant-...` sem espaços |
| "A chave da API da IA é inválida" | Chave copiada pela metade | Crie outra chave no console.anthropic.com e cole de novo |
| "A IA está com muitas requisições / sobrecarregada" | Instabilidade momentânea da API | Espere alguns minutos e toque de novo |
| Análise não aparece e nada acontece | Migração 0012 não rodou | Refaça a Parte B |
| E-mail do evento não chegou | SMTP fora do ar ou spam | Painel → relatório de e-mails mostra o erro exato; veja também a caixa de spam |
| Ficha do pediatra sem a seção de novidades | Não há eventos desde a última consulta | Registre um marco de teste (G.2) e gere um link novo |
| Rodapé ainda mostra v0.6.0 | Cache do PWA | Parte F: fechar o app por completo e reabrir |
