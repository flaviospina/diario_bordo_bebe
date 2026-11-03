# Guia de atualização — Rodada 6 (versão 0.11.0)

> **100% por cliques.** GitHub (baixar) → FileZilla (subir) → navegador
> (migração) → app (carregar a semana-modelo) → phpMyAdmin + Painel (novidade).
> Tempo: 15–20 minutos. Nada do que já está registrado é apagado.

O que esta rodada instala (o "Pacote Integração Compras" + o que você pediu):
1. **Cardápio da semana montado pelos pais** — menu **Compras**: a semana-modelo
   do pacote (Semana 1 — introdução alimentar, 02 a 08/11/2026: 28 itens em
   6 categorias e o cardápio dos 7 dias) entra com um clique e fica como
   **modelo padrão**; os pais escrevem, em cada dia, o que a cuidadora deve
   preparar (pode ser diferente do modelo).
2. **Aviso à cuidadora a cada alteração** — ao salvar o cardápio (e ao carregar
   ou criar uma semana) ela recebe aviso no app (sino), notificação no celular
   e e-mail.
3. **Cartão "Cardápio de hoje"** — no Meu Dia da cuidadora e no Acompanhar dos
   pais, com o botão **Registrar almoço 📷** que já vem com a refeição e os
   alimentos do cardápio marcados.
4. **Foto do prato obrigatória** no almoço e no jantar da introdução alimentar
   (checada no celular e no servidor; "Não feito" dispensa a foto).
5. **Lista de compras no celular** — por categoria, com quantidade e nota;
   marcação "no carrinho" salva no banco para toda a família (pais e babá
   marcam; avós só veem), progresso, filtros Pendentes / No carrinho,
   impressão, **Desmarcar tudo** (com confirmação), incluir/remover itens.
6. **Novas semanas a partir do modelo** sem duplicar (uma semana por data de
   início) e **importação segura** das marcações do HTML antigo (o app lê só o
   bloco `embedded-state`; nenhum script do arquivo é executado).
7. Atalho "Compras semanais e cardápio" em Suprimentos, item **Compras** no
   menu, tutorial atualizado e novidade pronta para enviar a todas as famílias.

Regra mantida do pacote: **comprar ≠ consumir** — marcar um item no carrinho
não cria refeição nem evento médico.

---

## PARTE 0 — Baixar (GitHub)
`github.com/flaviospina/diario_bordo_bebe` → branch **`claude/diario-bebe-webapp-4m1syg`**
→ **Code → Download ZIP** → extrair.
**✅** existem `app/Dados/cardapio_modelo_semana1.php` e
`database/migrations/0016_cardapio_compras.php`.

## PARTE A — Subir (FileZilla)
Pastas **`app`**, **`assets`**, **`config`**, **`database`**, **`docs`** → arrastar
para `/public_html/diariobebe` → Sobrescrever → fila zerada.
(Não suba `.env` nem `storage`.)
**✅** rodapé do app em **v0.11.0** (Ctrl+F5 / fechar e reabrir o app).

## PARTE B — Migração 0016 (navegador)
`https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
(o token está em `.env` → `MIGRATE_TOKEN`, visível no cPanel → Gerenciador de
arquivos → `/public_html/diariobebe/.env` → Editar).
**✅** linha verde "Migração executada: 0016_cardapio_compras.php — Rodada 6…".
Ela só **cria** três tabelas novas (`cardapios_semanas`, `cardapio_dias`,
`compras_itens`); não altera nenhuma tabela existente.

## PARTE C — Carregar a semana-modelo (no app, como responsável)
1. Entre com a sua conta → menu **Compras**.
2. Cartão **Semanas** → **Carregar a semana-modelo**.
   **✅** mensagem "Semana-modelo carregada: 28 itens em 6 categorias e o
   cardápio dos 7 dias". Clicar de novo não duplica nada.
3. Na tabela do cardápio, ajuste o que quiser (ex.: preencha o **Jantar**, que
   vem vazio no modelo) → **Salvar cardápio e avisar a cuidadora**.
   **✅** a babá recebe o aviso no sino, no celular (se ativou as notificações)
   e por e-mail.
4. Para a semana seguinte: **Nova semana a partir de** → data da segunda-feira
   → "Copiar itens do modelo" marcado → **Criar semana**.

## PARTE D — Novidade para as famílias
phpMyAdmin → banco → SQL → cole `database/dados/novidade_rodada6.sql` → Executar.
Depois **Painel → Novidades → "Enviar e-mail"** na novidade
"Cardápio da semana montado pelos pais, lista de compras no celular e almoço
registrado com foto".

## PARTE E — Testar (5 min)
1. **Babá:** Meu Dia → cartão **Cardápio de hoje** mostra as refeições →
   **Registrar almoço 📷** → refeição "Almoço" e os alimentos já marcados →
   tentar salvar **sem foto** → o app pede a foto → tirar a foto → Salvar.
2. **Lista:** Compras → tocar em 2 itens → recarregar a página → continuam
   marcados; abrir na conta dos pais → mesmo estado e barra de progresso.
3. **Desmarcar tudo** → confirmação → todos voltam a pendentes.
4. **Importar:** Compras → "Importar marcações" → escolher o HTML antigo da
   lista → tela de prévia (quantos itens serão marcados) → Aplicar.
5. **Tutorial:** rodapé → Como usar → seções "Cardápio do dia…" (babá) e
   "Cardápio da semana e compras semanais" (pais).

## Se algo falhar
| Sintoma | O que fazer |
|---|---|
| Menu Compras abre "Ops!" | Parte B não rodou (tabelas ausentes) |
| Cartão "Cardápio de hoje" diz "Nenhuma semana montada" | A data de hoje está fora das semanas criadas — Parte C.4 cria a próxima |
| "Registrar almoço" não pré-marca alimentos | O texto do dia não bate com o catálogo (ex.: "sopa") — marque à mão; frutas e legumes do modelo são reconhecidos |
| Babá não recebeu e-mail do cardápio | Painel → Conta de e-mail → "Enviar e-mail de teste"; o aviso no sino chega mesmo assim |
| Importar diz "não encontrei o estado salvo" | O HTML não é o da lista original (precisa do bloco `embedded-state`) |
| Rodapé não muda para 0.11.0 | Cache: fechar o app e reabrir; Ctrl+F5 no PC |
