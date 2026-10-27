# Guia de atualização — Rodada 5 (versão 0.10.0)

> **100% por cliques.** GitHub (baixar) → FileZilla (subir) → navegador
> (migração) → Painel (e-mail e novidade). Tempo: 20–25 minutos.
> **Nesta rodada o `.env` deixa de ser necessário para o e-mail**: a conta
> de envio passa a ser configurada no Painel.

O que esta rodada instala:
1. **Introdução alimentar por idade** — nova atividade "Introdução alimentar"
   com a lista de alimentos filtrada pela idade do bebê (106 alimentos em 9
   grupos; alergênicos ⚠️ e risco de engasgo ✂️ sinalizados), página pública
   com as fases e as fontes (MS, SBP, OMS, ESPGHAN, AAP) e a lista do que o
   bebê já experimentou na ficha e na ficha do pediatra.
2. **Foto em qualquer registro** — também no registro de várias atividades e
   na edição.
3. **Pedidos de compra que chegam** — pedido pela babá (em Itens ou em
   "Pedido de suprimento") entra na lista e manda e-mail aos responsáveis.
4. **E-mail dos momentos que importam** — sintoma, febre (≥ 37,8 °C),
   medicação, vômito e reação a alimento, além de marco, intercorrência,
   medição e vacina.
5. **Conta de e-mail pelo Painel** — remetente, nome, servidor SMTP, porta,
   segurança, usuário e senha (criptografada) direto no Painel, com teste.
6. Tutorial atualizado e novidade pronta para enviar a todas as famílias.

---

## PARTE 0 — Baixar (GitHub)
`github.com/flaviospina/diario_bordo_bebe` → branch **`claude/diario-bebe-webapp-4m1syg`**
→ **Code → Download ZIP** → extrair.
**✅** existem `app/Dados/alimentos.php` e `database/migrations/0015_rodada5_eventos_alimentacao.php`.

## PARTE A — Subir (FileZilla)
Pastas **`app`**, **`assets`**, **`config`**, **`database`**, **`install`** → arrastar
para `/public_html/diariobebe` → Sobrescrever → fila zerada.
**✅** rodapé do app em **v0.10.0** (Ctrl+F5 / fechar e reabrir o app).

## PARTE B — Migração 0015 (navegador)
`https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
**✅** linhas verdes "Migração executada: 0015…" **e** "Seed de categorias: 1 categoria(s)
global(is) inserida(s)" (é a Introdução alimentar).

## PARTE C — Conta de e-mail pelo Painel (resolve o 535 de vez)
1. Entre como super admin → **Painel** → cartão **"Conta de e-mail de envio"**.
2. Preencha:
   - E-mail do Diário do Bebê (remetente): `no-reply@itthrive.com.br`
   - Nome: `Diário do Bebê`
   - Servidor SMTP: `mail.itthrive.com.br` · Porta `465` · Segurança **SSL**
   - Usuário SMTP: `no-reply@itthrive.com.br`
   - Senha: **a senha atual da conta** (se não tiver certeza, defina uma nova no
     cPanel → Contas de e-mail → Gerenciar → Nova senha, só letras e números,
     e teste no webmail antes).
3. **Salvar conta de e-mail** → o cartão de diagnóstico passa a dizer
   "Configuração em uso agora: a do Painel".
4. **"Enviar e-mail de teste para mim"** → mensagem verde e o e-mail na caixa.
   Se vier `535`, a senha digitada ainda não é a da conta.

**✅** teste verde. A partir daqui, trocar a senha é só no Painel.

## PARTE D — Novidade para as famílias
phpMyAdmin → banco → SQL → cole `database/dados/novidade_rodada5.sql` → Executar.
Depois **Painel → Novidades → "Enviar e-mail"** na novidade
"Introdução alimentar por idade, foto em qualquer registro e e-mails…". (Se a
novidade da Rodada 4 ainda não foi enviada, envie as duas — o e-mail macro é
o que você pediu para "informar a todos".)

## PARTE E — Testar (5 min)
1. **Alimentação:** Meu Dia → + → Alimentação → **Introdução alimentar** →
   a lista vem filtrada pela idade do bebê; marque 2 alimentos → Salvar →
   abra "Dados do bebê": aparece "Introdução alimentar — 2 alimentos".
2. **Pedido de compra:** entre como babá → Itens → "Fraldas M" → Pedir →
   o e-mail "Pedido de compra: Fraldas M" chega aos responsáveis.
3. **Foto:** + → "Selecionar várias" → 2 atividades → há o campo Foto.
4. **Guia:** rodapé → Como usar → seção "Introdução alimentar" → link do guia.

## Se algo falhar
| Sintoma | O que fazer |
|---|---|
| "Introdução alimentar" não aparece na janela + | Parte B (seed) ou a categoria está em Ajustes → Categorias desativada |
| Lista de alimentos vazia | Bebê sem data de nascimento (Ajustes → Crianças) — sem idade, tudo aparece com a idade mínima |
| E-mail de teste com 535 | Senha no Painel ≠ senha da conta (Parte C.2) |
| Rodapé não muda para 0.10.0 | Cache: fechar o app e reabrir; Ctrl+F5 no PC |
