# Guia de atualização — Rodada 7 (versão 0.12.0)

> **100% por cliques.** GitHub (baixar) → FileZilla (subir) → navegador
> (migração) → app (vincular a babá e importar o primeiro contracheque) →
> phpMyAdmin + Painel (novidade). Tempo: 20 minutos. Nada do diário é apagado.

O que esta rodada instala:
1. **Portal da Colaboradora** — área separada do diário, com o mesmo login:
   - **Babá:** "Meus Pagamentos" (resumo da última competência, contracheques,
     vale-transporte, horas extras, férias/13º, meus documentos, histórico
     mensal), visualizar e baixar PDF, botão "Voltar ao Diário do Bebê".
     Sem botão de ciência/aceite.
   - **Empregador:** "Gestão da Colaboradora" (Configurações → ou menu
     "Colaboradora"): vincular a babá, importar PDFs do eSocial, conferir
     valores (sugestões lidas do PDF), publicar, substituir com versionamento,
     revogar, vale-transporte antecipado, pagamentos por competência e
     histórico de acessos (primeiro/último acesso, aberturas, downloads).
2. **Auditoria automática** — abrir ou baixar um PDF gera o evento no
   servidor (por usuário, documento e versão), com proteção contra requisições
   técnicas repetidas. Tentativas negadas também ficam registradas.
3. **Segurança** — PDFs em `storage/folha/` (área privada, `.htaccess`
   "Require all denied"), nomes aleatórios, hash SHA-256, validação de PDF real
   (cabeçalho, fim, MIME, sem scripts/anexos), limite configurável, download só
   autenticado e autorizado, isolamento por família, sem cache no PWA.
4. **Registro direto pelos pais** — botão "Registrar atividade" + ações
   rápidas no Acompanhar (igual ao da cuidadora), além do "+" global.
5. Tutorial atualizado e novidade pronta para enviar a todas as famílias.

**O que NÃO muda:** login, usuários, registros do bebê, categorias, roteiro,
compras/cardápio. O eSocial continua a fonte oficial — o app não calcula
tributos, não acessa o Gov.br e não guarda senha de ninguém.

---

## PARTE 0 — Baixar (GitHub)
`github.com/flaviospina/diario_bordo_bebe` → branch **`claude/diario-bebe-webapp-4m1syg`**
→ **Code → Download ZIP** → extrair.
**✅** existem `database/migrations/0017_portal_colaboradora.php` e
`app/Controllers/FolhaAdminController.php`.

## PARTE A — Subir (FileZilla)
Pastas **`app`**, **`assets`**, **`config`**, **`database`**, **`docs`** e o
arquivo **`storage/folha/.htaccess`** (crie a pasta `storage/folha` no
servidor se não existir e suba só o `.htaccess` dela) → `/public_html/diariobebe`
→ Sobrescrever → fila zerada. (Não suba `.env` nem o resto de `storage`.)
**✅** rodapé do app em **v0.12.0** (Ctrl+F5 / fechar e reabrir o app).

## PARTE B — Migração 0017 (navegador)
`https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
**✅** linha verde "Migração executada: 0017_portal_colaboradora.php — Rodada 7…".
Ela só **cria** sete tabelas (`colaboradoras`, `folha_permissoes`,
`folha_documentos`, `folha_versoes`, `folha_resumos`, `folha_vales_transporte`,
`folha_eventos`); nada existente é alterado.

## PARTE C — Limite de upload (opcional, cPanel)
O limite do app é **10 MB** por PDF (variável `FOLHA_MAX_MB` no `.env`, só se
quiser mudar). Os contracheques do eSocial têm ~100 KB. Se o servidor recusar
um PDF maior que 8 MB, o app avisa "maior que o limite do servidor": nesse caso
cPanel → **Select PHP Version → Options** → `upload_max_filesize` e
`post_max_size` = `16M`.

## PARTE D — Primeiro uso (no app, como admin da família)
1. **Configurações → Gestão da Colaboradora** → aba **Colaboradoras** →
   escolha a babá → data de admissão → **Vincular**.
2. **Importar documento** → tipo *Contracheque* → competência (ex.: 10/2026)
   → data do pagamento → escolha o PDF do eSocial → **Enviar e conferir**.
3. Na tela do documento: confira a prévia e os valores (os lidos do PDF
   aparecem como sugestão), marque **"Conferi estes valores"** → **Salvar
   valores** → **Publicar para a colaboradora**.
   **✅** mensagem verde; a babá recebe aviso (sino/celular/e-mail sem valores).
4. **Benefícios** → vale-transporte: mês de referência (ex.: 2026-11), pago
   em (30/10/2026), competência da folha (2026-10), R$ 22,00 × dias →
   **Salvar**. Repetir o mesmo mês apenas atualiza (não duplica).
5. Entre como babá → Meu Dia → cartão **Meus pagamentos** → abra o
   contracheque → **Visualizar PDF**. Volte à gestão → documento →
   **Histórico de acessos** mostra a abertura com data e hora.

## PARTE E — Novidade para as famílias
phpMyAdmin → banco → SQL → cole `database/dados/novidade_rodada7.sql` → Executar.
Depois **Painel → Novidades → "Enviar e-mail"** na novidade "Portal da
Colaboradora…".

## Se algo falhar
| Sintoma | O que fazer |
|---|---|
| Menu "Colaboradora" abre aviso "ainda não foi instalado" | Parte B não rodou |
| "Acesso negado" na gestão | Só o admin da família gere; conceda a outro responsável em Colaboradoras → "Quem pode gerir" |
| Babá vê "Seu vínculo ainda não foi cadastrado" | Parte D.1 |
| PDF recusado ("não é um PDF válido" / "conteúdo ativo") | Baixe de novo do eSocial; PDFs com scripts/anexos não são aceitos |
| "maior que o limite do servidor" | Parte C |
| Prévia em branco no celular | Use "Abrir em nova aba" ou "Baixar" — alguns navegadores móveis não exibem PDF embutido |
| Publicar diz "Confira e salve os valores" | Marque a caixa "Conferi estes valores" e salve antes de publicar |
