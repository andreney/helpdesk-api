# Regras de Negócio — Helpdesk API

Este documento registra as regras implementadas e distingue os requisitos ainda não aplicados pelo código. Para campos e relacionamentos do banco, consulte [database.md](database.md).

## Usuários e perfis

Os perfis são ADMIN, AGENT e USER. A API exige autenticação Sanctum em todas as rotas, exceto POST /api/v1/login.

| Ação | ADMIN | AGENT | USER |
|---|---|---|---|
| Criar ticket | Permitido | Permitido | Permitido |
| Listar tickets | Todos | Todos | Somente os próprios |
| Visualizar ticket | Qualquer ticket | Qualquer ticket | Somente os próprios |
| Alterar status | Permitido | Permitido | Negado |
| Comentar | Qualquer ticket | Qualquer ticket | Somente nos próprios |

O ticket criado fica associado ao usuário autenticado. Valores de user_id enviados no corpo da criação são ignorados.

## Categorias

Toda criação de ticket exige um category_id inteiro que corresponda a uma categoria existente.

Categorias inativas não podem ser usadas em novos tickets: a validação exige que `category_id` aponte para uma categoria existente **e ativa**. As categorias disponíveis são listadas em `GET /api/v1/categories`.

Categorias relacionadas a tickets devem ser desativadas em vez de removidas, para preservar o histórico.

## Tickets

- title, description, category_id e priority são obrigatórios na criação.
- title e description devem ser texto; title aceita até 150 caracteres e description até 5000.
- priority aceita LOW, MEDIUM, HIGH ou URGENT.
- Todo ticket novo inicia em OPEN.
- O usuário solicitante é obtido da autenticação, não do payload.
- O histórico de comentários pertence ao ticket e deve ser preservado.

## Ciclo de status

A API aceita os seguintes status e transições:

| Status atual | Próximos status permitidos |
|---|---|
| OPEN | IN_PROGRESS |
| IN_PROGRESS | WAITING ou CLOSED |
| WAITING | IN_PROGRESS |
| CLOSED | Nenhum |

Não é permitido fechar diretamente um ticket OPEN. Quando o ticket passa para CLOSED, closed_at recebe a data do encerramento. Nas outras transições permitidas, closed_at permanece nulo.

Somente ADMIN e AGENT podem solicitar a mudança de status. Além da permissão, a transição precisa estar na matriz acima. A permissão é verificada antes da validação do corpo da requisição.

A matriz de transições vive no enum `TicketStatus` (`allowedTransitions()`), e a API a expõe em `allowed_transitions` para que clientes não precisem duplicá-la.

## Responsável pelo ticket

Todo ticket nasce sem responsável. Ao passar para IN_PROGRESS, se ainda não houver responsável, quem executou a ação assume o ticket. Um responsável já definido é mantido nas transições seguintes. Reatribuição manual ainda não existe.

## Comentários

- comment é obrigatório, texto e limitado a 5000 caracteres.
- O autor é sempre o usuário autenticado; user_id enviado no payload é ignorado.
- O ticket é determinado pela rota; ticket_id enviado no payload é ignorado.
- USER pode comentar apenas em ticket próprio.
- ADMIN e AGENT podem comentar em qualquer ticket.
- Comentários formam o histórico do atendimento; não há rota para excluí-los.

## Respostas e erros principais

- 201: ticket ou comentário criado.
- 200: listagem, visualização ou alteração de status concluída.
- 401: ausência de autenticação ou credenciais inválidas.
- 403: usuário autenticado sem permissão.
- 404: ticket solicitado não encontrado.
- 422: validação inválida ou transição de status não permitida.

## Regras ainda não implementadas

- Reatribuição manual do agente responsável.
- Definir operações de edição e exclusão de tickets, que ainda não têm rotas.
