# Guia de atualização — Rodada 4 (versão 0.9.0)

> **100% por cliques** — nada de terminal. Você vai usar: o **GitHub**
> (baixar), o **FileZilla** (subir), o **cPanel** (e-mail, `.env`,
> phpMyAdmin) e o **navegador/celular** (migração e testes).
>
> **Siga na ordem.** Cada parte termina com uma **✅ Verificação**.
> Tempo total: 25–35 minutos.

O que esta rodada instala:
1. **Registro 24 h para os pais** — a janela de horário segue valendo para a
   babá; os responsáveis registram de dia e de noite e o Meu Dia deles
   mostra o dia inteiro. Registros fora da janela aparecem em "Fora da janela".
2. **Botão + em toda tela** — o botão redondo de registrar está em qualquer
   página; toque e a janela de registro abre na hora atual.
3. **Sino de avisos + notificações no celular (push)** — a babá recebe os
   avisos das ações dos pais que afetam o trabalho dela; os pais recebem os
   avisos que já chegam por e-mail. Com "Ativar notificações", chegam como
   mensagem no celular, mesmo com o app fechado.
4. **Diagnóstico de e-mail no Painel** — mostra o que o `.env` está entregando
   ao SMTP (sem a senha) e tem o botão **"Enviar e-mail de teste para mim"**,
   que mostra a resposta exata do servidor quando falha.
5. **Correção importante de bastidor** — a política de segurança (CSP) do app
   bloqueava o script que registra o PWA no celular. Agora o service worker
   registra de verdade: é o que torna o push (e o modo offline) possível.

---

## PARTE 0 — Baixar o código (GitHub)

1. `github.com/flaviospina/diario_bordo_bebe` → branch
   **`claude/diario-bebe-webapp-4m1syg`** → **`<> Code`** → **Download ZIP** → extrair.

**✅ Verificação:** existem `app/Core/WebPush.php` e
`database/migrations/0014_avisos_push.php` na pasta extraída.

## PARTE A — Subir os arquivos (FileZilla)

1. Conecte em `ftp.itthrive.com.br` → lado direito em `/public_html/diariobebe`.
2. Selecione (Ctrl) as pastas **`app`**, **`assets`**, **`config`**,
   **`database`** e **`install`** → arraste → **Sobrescrever** + "Sempre usar
   esta ação" → espere a fila zerar.

**✅ Verificação:** no app, o rodapé mostra **v0.9.0** (recarregue com Ctrl+F5
no computador; no celular, feche o app por completo e reabra).

## PARTE B — Migração 0014 (navegador)

1. `https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
2. Deve aparecer **"Migração executada: 0014_avisos_push.php — Rodada 4…"**.

**✅ Verificação:** no topo do app aparece o **sino** ao lado do livrinho.

## PARTE C — Consertar o e-mail (o erro 535)

O `535 Incorrect authentication data` é o servidor de e-mail recusando a
senha da conta `no-reply@itthrive.com.br`. Faça nesta ordem:

### C.1 Descubra o que o servidor está recebendo
1. Entre como super admin → **Painel** → cartão **"E-mail (SMTP) — diagnóstico"**.
2. Confira: Servidor `mail.itthrive.com.br:465 (ssl)`, Usuário
   `no-reply@itthrive.com.br`, Senha **definida** e **nenhum aviso amarelo**
   (aspas, espaços, usuário sem @). Se houver aviso, corrija no `.env`
   (passo C.3) antes de seguir.

### C.2 Alinhe a senha da conta
1. cPanel → **Contas de e-mail** → `no-reply@itthrive.com.br` → **Gerenciar**.
2. Em **Nova senha**, defina uma senha **só com letras e números** (12+
   caracteres; evite `#`, aspas, espaços e `$`, que quebram o `.env`) → **Salvar**.
3. Teste a senha no **Webmail** (`https://itthrive.com.br/webmail`): se entrar,
   a senha está correta.

### C.3 Coloque a mesma senha no `.env`
1. cPanel → **Gerenciador de Arquivos** → pasta do `.env` (ative "Mostrar
   arquivos ocultos" em Configurações, se preciso) → `.env` → **Edit**.
2. A linha deve ficar exatamente assim (sem espaços, sem aspas):
   ```
   SMTP_SENHA=ASenhaNovaAqui
   ```
   Confira também: `SMTP_HOST=mail.itthrive.com.br`, `SMTP_PORTA=465`,
   `SMTP_SEGURANCA=ssl`, `SMTP_USUARIO=no-reply@itthrive.com.br`.
3. **Save Changes**.
4. Se o workflow de alerta do n8n usa essa conta, atualize a senha lá também.

**✅ Verificação:** Painel → **"Enviar e-mail de teste para mim"** → a mensagem
verde "E-mail de teste enviado" aparece e o e-mail chega na sua caixa. Se vier
vermelho, a frase mostra a resposta exata do servidor: `535` = senha ainda
diferente (refaça C.2/C.3); `Sem conexão` = host/porta errados.

Depois disso, clique em **"Enviar lembretes pendentes agora"**: os e-mails que
falharam saem automaticamente (as tentativas falhadas não contam nas travas).

## PARTE D — Novidade para as famílias (phpMyAdmin)

phpMyAdmin → banco → aba **SQL** → cole o conteúdo de
`database/dados/novidade_rodada4.sql` → **Executar**. (Opcional: Painel →
Novidades → "Enviar e-mail".)

## PARTE E — Ativar as notificações no celular (você e a babá)

1. No celular, abra o app **pelo ícone da tela inicial** (no iPhone isso é
   obrigatório; no Android funciona também pelo Chrome).
2. Toque no **sino** → **"Ativar notificações"** → **Permitir**.
3. Toque em **"Enviar um teste"**: a notificação deve aparecer em segundos.
4. Repita no celular da babá (com a conta dela).

> iPhone: precisa de iOS 16.4 ou mais novo e o app adicionado à Tela de Início
> (Safari → Compartilhar → "Adicionar à Tela de Início"). Abrindo pelo Safari
> comum, o botão explica isso em vez de ativar.

**✅ Verificação:** com o app fechado, peça para o outro responsável ajustar um
registro da babá (ou mudar o roteiro). A notificação chega no celular dela.

## PARTE F — Testar o resto (5 min)

1. **24 h:** entre como pai/mãe → Meu Dia mostra "registro liberado 24 h";
   registre algo às 23:30 → aparece no dia.
2. **Botão +:** em Acompanhar (ou Relatórios), toque no botão redondo → a
   janela de registro abre na hora atual.
3. **Avisos:** como pai, ajuste um registro da babá com um motivo → entre como
   babá: o sino mostra "1" e a lista traz "Flávio ajustou o registro…".

## PARTE G — Se algo falhar

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| Sino não aparece | Migração 0014 não rodou | Parte B |
| Rodapé ainda v0.8.x | Cache do PWA | Fechar o app por completo e reabrir; Ctrl+F5 no PC |
| "Este navegador não suporta notificações" | Safari sem app na tela inicial, ou navegador antigo | Parte E, nota do iPhone; no Android use o Chrome |
| Teste de push não chega | Permissão negada no sistema | Ajustes do celular → Notificações → Chrome/app → permitir; depois "Desativar" e "Ativar" de novo |
| E-mail de teste com 535 | Senha do `.env` ≠ senha da conta | Parte C.2 e C.3 |
| Registro das 23h some do Meu Dia da babá | Fora da janela de trabalho dela | Está na seção "Fora da janela", logo abaixo da grade |
