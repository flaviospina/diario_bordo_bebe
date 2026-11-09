# Guia de atualização — Rodada 8 (versão 0.12.1)

> **100% por cliques.** GitHub (baixar) → FileZilla (subir) → navegador
> (migração) → app (importar o planejamento com prévia). Tempo: 10 minutos.
> Nada do que já está registrado é alterado.

O que esta rodada instala:
1. **Importação do planejamento alimentar de 09 a 30/11/2026** (pacote
   "Refeições Zuno"): 22 dias, 88 refeições (44 frutas, 22 almoços e 22
   jantares), com ovo em 18, 20, 25 e 30/11 e peixe em 28 e 30/11 sinalizados
   para confirmação com a pediatra. Status sempre **Planejado**.
2. **Prévia antes de aplicar** (nada é gravado na prévia) e **importação
   idempotente**: repetir não duplica; o que vocês escreveram à mão é
   preservado, salvo se marcarem "substituir também" na prévia.
3. **Alertas ⚠️ de ovo/peixe** no cartão "Cardápio de hoje" da cuidadora e
   na tabela do cardápio, só nos dias correspondentes; se vocês trocarem o
   alimento, o alerta some.
4. O que **não** muda: lista de compras de 02–08/11 e marcações, refeições
   registradas pela cuidadora, sintomas, medições, pagamentos. Não cria
   listas de compras novas (as semanas novas nascem sem itens; adicione
   quando quiser).

---

## PARTE 0 — Baixar (GitHub)
`github.com/flaviospina/diario_bordo_bebe` → branch **`claude/diario-bebe-webapp-4m1syg`**
→ **Code → Download ZIP** → extrair.
**✅** existem `app/Dados/planejamento_zuno_nov2026.php` e
`database/migrations/0018_planejamento_alimentar.php`.

## PARTE A — Subir (FileZilla)
Pastas **`app`**, **`assets`**, **`config`**, **`database`**, **`docs`** →
`/public_html/diariobebe` → Sobrescrever. (Não suba `.env` nem `storage`.)
**✅** rodapé em **v0.12.1**.

## PARTE B — Migração 0018 (navegador)
`https://itthrive.com.br/diariobebe/install/migrate.php?token=SEU_MIGRATE_TOKEN`
**✅** "Migração executada: 0018_planejamento_alimentar.php". Ela só adiciona
duas colunas (`planejado_json`, `alertas_json`) em `cardapio_dias`.

## PARTE C — Importar o planejamento (no app, como responsável)
1. Menu **Compras** → cartão **Semanas** → **Importar planejamento 09/11–30/11**.
2. Confira a **prévia**: quantas refeições são novas, quantas já estão iguais
   e quantas vocês editaram (preservadas). Dias com ⚠️ ovo/peixe aparecem
   marcados.
3. **Aplicar planejamento e avisar a cuidadora**.
   **✅** mensagem verde "Planejamento de 09/11 a 30/11 aplicado: … refeições
   novas…"; a babá recebe aviso no sino/celular/e-mail.
4. Pode repetir quando quiser: a segunda vez diz "88 já idênticas · Nada mudou".

## PARTE D — Conferir
- Babá → Meu Dia → navegue até 18/11: cartão "Cardápio de 18/11 · Planejado"
  com ⚠️ Ovo e a orientação; 28/11 mostra ⚠️ Peixe.
- O almoço/jantar registrados pela cuidadora continuam exigindo foto e
  vêm pré-preenchidos com o planejado do dia (ajustáveis).

## Se algo falhar
| Sintoma | O que fazer |
|---|---|
| Botão "Importar planejamento" não aparece | Só responsáveis veem; confira o login |
| Prévia diz "Arquivo de planejamento inválido" | A pasta `app/Dados` não foi atualizada (Parte A) |
| "A importação falhou e nada foi gravado" | Parte B não rodou (colunas novas ausentes) |
| Alerta ⚠️ sumiu | Vocês trocaram o alimento daquele dia — o alerta acompanha o texto |
