-- ============================================================
-- Diário do Bebê — Novidade da Rodada 4 (v0.9.0)
--
-- COMO USAR (phpMyAdmin, sem terminal):
--   1. cPanel → phpMyAdmin → clique no banco do Diário do Bebê (à esquerda)
--   2. Aba "SQL" → cole TODO este conteúdo → botão "Executar" (Go)
--
-- Pode rodar quantas vezes quiser: o INSERT IGNORE usa o slug único e
-- nunca duplica. O e-mail da novidade é disparado pelo Painel, quando
-- você quiser (botão "Enviar e-mail").
-- ============================================================

INSERT IGNORE INTO novidades (slug, titulo, resumo, detalhes, publicado, criado_por)
VALUES
(
  'avisos-no-celular-e-registro-24h',
  'Avisos no celular, botão + em toda tela e registro 24 h para os pais',
  'O app agora avisa na hora: o sino no topo reúne o que mudou, e as notificações chegam no celular mesmo com o app fechado. O botão + de registro está em todas as telas, e os pais registram de dia e de noite.',
  'Sino de avisos: no topo de qualquer tela há um sino com o número de avisos novos. Toque nele para ver a lista — a babá fica sabendo quando um responsável ajusta ou exclui um registro, decide uma solicitação, muda o roteiro, resolve um item da lista de compras ou registra uma medicação à noite. Os pais recebem ali os mesmos avisos que já chegam por e-mail (intercorrências, solicitações, ficha aberta pelo pediatra).

Notificações no celular: na página de Avisos, toque em "Ativar notificações" e permita no navegador. A partir daí, os avisos chegam como uma mensagem no celular, mesmo com o app fechado. No iPhone, é preciso ter o app adicionado à Tela de Início (Compartilhar → Adicionar à Tela de Início) e abri-lo por esse ícone.

Botão + em toda tela: o botão redondo de registrar agora aparece em qualquer página — toque nele e a janela de registro abre na hora atual, sem precisar voltar ao Meu Dia.

Registro 24 horas para os pais: a janela de horário (ex.: 7h às 19h) continua valendo para o trabalho da babá, mas os responsáveis registram a qualquer hora, de dia e de madrugada — e o Meu Dia deles mostra o dia inteiro. Registros fora da janela nunca somem: aparecem na seção "Fora da janela".',
  1,
  (SELECT id FROM usuarios ORDER BY (papel = 'super_admin') DESC, id LIMIT 1)
);

SELECT slug, titulo, publicado, criado_em FROM novidades ORDER BY id;
