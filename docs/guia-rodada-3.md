# Guia de atualização — Rodada 3 (versão 0.8.0)

> **100% por cliques** — nada de terminal ou comandos no seu computador.
> Você vai usar: o **GitHub** (baixar o código), o **FileZilla** (subir os
> arquivos), o **cPanel** (phpMyAdmin e cron) e o **navegador** (migração e
> testes). Nenhuma configuração nova de `.env` é necessária nesta rodada.
>
> **Siga na ordem, sem pular etapas.** Cada parte termina com uma
> **✅ Verificação** — só avance quando ela passar.
> Tempo total: 20–25 minutos.

O que esta rodada instala:
1. **Dashboard "Saúde das famílias"** — no topo do Painel: quem está ativa,
   esfriando, inativa ou nunca começou; último acesso, último registro,
   atividades em 7/30 dias e os lembretes já enviados. Somente metadados —
   o conteúdo dos diários continua invisível.
2. **5 e-mails automáticos de engajamento** (os que você aprovou):
   resgate (diário zerado há 3+ dias), reengajamento (7+ dias parada),
   resumo do mês, mêsversário com lembrete de pesagem e aviso 2 dias antes
   da consulta. Com travas anti-spam: no máximo 1 por semana e 3 tentativas
   por família; tudo registrado no relatório de e-mails.
3. **Seção "Contato e suporte" no Painel** — o seu WhatsApp (11 99335-8259,
   já configurado) aparece como botão nos e-mails de ajuda; para trocar o
   número, é um formulário no Painel — sem mexer em arquivo.

---

## PARTE 0 — Baixar o código atualizado (GitHub)

1. Abra `github.com/flaviospina/diario_bordo_bebe` no navegador.
2. No seletor de **branch**, escolha **`claude/diario-bebe-webapp-4m1syg`**.
3. Botão verde **`<> Code`** → **Download ZIP** → botão direito →
   **Extrair tudo**.

**✅ Verificação:** na pasta extraída existem
`app/Services/ServicoEngajamento.php` e
`database/migrations/0013_configuracoes_plataforma.php`.

---

## PARTE A — Subir os arquivos (FileZilla)

Igual às rodadas anteriores:

1. FileZilla → `ftp.itthrive.com.br` → **Conexão rápida**.
2. Lado esquerdo: a pasta extraída. Lado direito: `/public_html/diariobebe`.
3. Selecione (com `Ctrl`) as pastas **`app`**, **`assets`**, **`config`**,
   **`database`** e **`install`** → arraste para o lado direito →
   **Sobrescrever** + "Sempre usar esta ação" → OK → espere a fila zerar.

**✅ Verificação:** entre no app com a sua conta super admin e abra o
**Painel**: a primeira seção deve ser **"Saúde das famílias"**, com os 4
números no topo. (Se não aparecer, o cache do navegador segurou o CSS —
recarregue com Ctrl+F5.)

---

## PARTE B — Rodar a migração 0013 (navegador)

1. Abra (troque `SEU_TOKEN` pelo `MIGRATE_TOKEN` do seu `.env`):

   `https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_TOKEN`

2. Deve aparecer:
   **"Migração executada: 0013_configuracoes_plataforma.php — Rodada 3…"**

**✅ Verificação:** no Painel, a seção **"Contato e suporte"** mostra o campo
WhatsApp já preenchido com **11993358259**.

---

## PARTE C — Ativar os e-mails automáticos (cron do cPanel)

> Sem este cron os e-mails não saem — é ele que roda a checagem diária.

1. cPanel → **Cron Jobs**.
2. Em **Add New Cron Job**, preencha:
   Minute `30` · Hour `9` · Day `*` · Month `*` · Weekday `*`
   (= todo dia às 9h30; pode rodar mais vezes sem risco — as travas impedem
   qualquer envio repetido).
3. No campo **Command**, cole (trocando `SEU_TOKEN` pelo `TAREFAS_TOKEN`
   do seu `.env`):

   ```
   curl -s -X POST -H "X-Token: SEU_TOKEN" https://itthrive.com.br/diariobebe/api/tarefas/engajamento
   ```

4. **Add New Cron Job**.

**✅ Verificação:** a linha aparece em "Current Cron Jobs". Para testar na
hora, abra uma aba anônima e aguarde o horário — ou confira no dia seguinte
o **relatório de e-mails** do Painel: os envios aparecem com os tipos
`resgate`, `reengajamento` etc.

---

## PARTE D — Publicar a novidade para as famílias (phpMyAdmin)

1. cPanel → **phpMyAdmin** → banco do Diário do Bebê → aba **SQL**.
2. Abra `database/dados/novidade_rodada3.sql` (da pasta extraída) com o
   Bloco de Notas → copie tudo → cole → **Executar**.
3. (Opcional) No Painel, seção Novidades → **"Enviar e-mail"**.

**✅ Verificação:** a consulta final lista a novidade
`lembretes-inteligentes-por-email`.

---

## PARTE E — Limpar o cache do celular ⭐

1. Feche o app completamente no celular e reabra.
2. O rodapé deve mostrar **v0.8.0**.

---

## PARTE F — Testar (5 min)

1. **Dashboard:** Painel → "Saúde das famílias" mostra as suas famílias
   reais com as situações (as que nunca registraram aparecem como
   "Nunca começou" — exatamente o que você queria enxergar).
2. **Contato:** troque o WhatsApp para outro número → Salvar → o campo
   volta preenchido com o novo valor → troque de volta.
3. **E-mails:** após o primeiro cron das 9h30, o relatório de e-mails do
   Painel mostra os envios; abra um deles na sua caixa de entrada e toque
   no botão verde **"💬 Chamar no WhatsApp"** — deve abrir a sua conversa.

---

## PARTE G — Se algo falhar (por sintoma)

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| Painel sem "Saúde das famílias" | Pastas `app`/`assets` não subiram inteiras | Refaça a Parte A |
| Erro ao abrir o Painel | Migração 0013 não rodou | Refaça a Parte B |
| Nenhum e-mail automático sai | Cron não criado ou token errado | Refaça a Parte C conferindo o `TAREFAS_TOKEN` |
| E-mail sai sem o botão de WhatsApp | Campo WhatsApp vazio no Painel | Preencha em "Contato e suporte" e salve |
| E-mails caem em spam | SPF/DKIM | Já configurado na Rodada 1 (Email Deliverability); confira se segue "Válido" |
| Famílias recebendo e-mail demais | Impossível pelas travas (1/semana, 3 tentativas) | Se acontecer, me avise com o print do relatório |
