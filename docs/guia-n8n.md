# Guia — Tarefas automáticas pelo n8n (substitui o cron do cPanel)

> **100% por cliques.** O n8n vira o "relógio" do Diário do Bebê: ele chama os
> endpoints de tarefas do app nos horários certos, no fuso de São Paulo.
> O app continua fazendo o resto — montar os e-mails, aplicar as travas
> anti-spam e registrar tudo no relatório do Painel. Nada precisa ser
> alterado no servidor da HostGator, e nenhum upload de arquivo é necessário.

Arquivos desta pasta (`integracao/n8n/`):

| Arquivo | O que é |
|---|---|
| `diario-bebe-tarefas.json` | Workflow principal: lembretes (toda hora), IA semanal (8h) e rotinas do diário (30 em 30 min) |
| `diario-bebe-alerta-erros.json` | Workflow opcional: te avisa por e-mail se alguma tarefa falhar |

---

## PARTE 0 — Baixar os arquivos

1. Abra `github.com/flaviospina/diario_bordo_bebe` → branch
   **`claude/diario-bebe-webapp-4m1syg`** → pasta **`integracao/n8n`**.
2. Clique em `diario-bebe-tarefas.json` → botão **Download raw file**
   (ícone de download, no canto direito do arquivo). Repita para
   `diario-bebe-alerta-erros.json`.

## PARTE 1 — Colocar o seu token no arquivo (antes de importar)

1. Abra o `diario-bebe-tarefas.json` baixado com o **Bloco de Notas**.
2. **Ctrl+H** (Substituir): em "Localizar" digite
   `SUBSTITUA_PELO_TAREFAS_TOKEN` e em "Substituir por" cole o valor do seu
   `TAREFAS_TOKEN` (o mesmo do `.env` — hoje: `diariobebe_token_tarefas_2026`).
3. **Substituir tudo** (são 3 ocorrências) → salve o arquivo.

## PARTE 2 — Importar e ativar no n8n

1. Na sua instância do n8n: **Workflows** → botão **⋯** (ou "Add workflow") →
   **Import from File** → escolha o `diario-bebe-tarefas.json` editado.
2. O workflow abre com 3 linhas (lembretes, IA, rotinas) e uma nota amarela
   com o resumo. Confira num dos nós HTTP se o header `X-Token` está com o
   seu token de verdade.
3. **Teste na hora:** clique no nó **"Lembretes às famílias"** → botão
   **Execute step**. Deve voltar `{"tarefa":"engajamento","ok":true,...}`.
   Se voltar "Token inválido", o token da Parte 1 não confere com o `.env`.
4. Ative o workflow (chave **Active** no topo, de cinza para verde).

**✅ Verificação:** na hora xx:25 seguinte, a aba **Executions** mostra a
execução — e no Painel do app o carimbo **"Último ciclo de lembretes"**
avança. É a prova de ponta a ponta.

## PARTE 3 — Alerta de erro por e-mail (opcional, recomendado)

1. Importe também o `diario-bebe-alerta-erros.json` (sem editar nada).
2. Abra o nó **"Avisar por e-mail"** → em **Credential to connect with**,
   selecione a sua credencial SMTP (ou **Create new**: host
   `mail.itthrive.com.br`, porta `465`, SSL, usuário `no-reply@itthrive.com.br`
   e a senha da conta) → salve e **ative** o workflow.
3. Volte ao workflow **"Diário do Bebê — Tarefas automáticas"** → menu **⋯**
   → **Settings** → **Error workflow** → selecione
   **"Diário do Bebê — Alerta de erro"** → Save.

Pronto: se o site sair do ar ou o token mudar, você recebe um e-mail com o
erro exato em vez de descobrir dias depois.

## PARTE 4 — Aposentar os crons do cPanel

Para não ter duas fontes de agendamento, exclua os crons do Diário do Bebê
no cPanel (**Cron Jobs** → **Excluir** na linha de cada um). Se esquecer
algum ligado, não há risco: as travas do app impedem envio duplicado —
só fica redundante.

---

## Como funciona (para referência)

```
n8n (VPS, fuso São Paulo)                HostGator (app PHP)
┌──────────────────────────┐   HTTPS    ┌─────────────────────────────┐
│ Toda hora :25 ───────────┼──POST────▶ │ /api/tarefas/engajamento    │
│ Todo dia 8h ─────────────┼──POST────▶ │ /api/tarefas/acompanhamento │
│ A cada 30 min ───────────┼──POST────▶ │ /api/tarefas/fila           │
│  (X-Token no header)     │            │  → travas anti-spam         │
│ Error workflow → e-mail  │            │  → SMTP próprio (SPF/DKIM)  │
└──────────────────────────┘            │  → relatório no Painel      │
                                        └─────────────────────────────┘
```

- **Por que o envio continua no app?** O SMTP do domínio já está com
  SPF/DKIM válidos (menos spam), cada envio fica auditado em
  `emails_enviados` (relatório do Painel) e as travas anti-spam moram junto
  dos dados. O n8n manda o "quando"; o app decide o "se" e o "para quem".
- **Rodar demais não duplica:** toda hora, o app reavalia e só envia o que
  está pendente — fora da janela 8h–21h ele apenas carimba a execução.
- O botão **"Enviar lembretes pendentes agora"** do Painel continua
  funcionando como sempre, independente do n8n.

## Se algo falhar

| Sintoma | O que fazer |
|---|---|
| Execute step devolve "Token inválido" | Refaça a Parte 1 — o token não bate com o `.env` |
| Execução vermelha na aba Executions | Abra-a e leia o erro do nó; com a Parte 3 ativa, o mesmo erro chega por e-mail |
| Carimbo do Painel não avança | O workflow está inativo (chave Active) ou a execução está falhando |
| E-mails saindo fora de hora | Impossível: o app trava os envios fora de 8h–21h de Brasília |
