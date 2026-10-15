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

A regra de negócio definida é que categorias inativas não devem ser usadas em novos tickets. **Essa regra ainda não está aplicada na validação atual da API**, que verifica a existência da categoria, mas não o campo active.

Categorias relacionadas a tickets devem ser desativadas em vez de removidas, para preservar o histórico.

## Tickets

- title, description, category_id e priority são obrigatórios na criação.
- title e description devem ser texto; title aceita até 150 caracteres.
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

Somente ADMIN e AGENT podem solicitar a mudança de status. Além da permissão, a transição precisa estar na matriz acima.

## Comentários

- comment é obrigatório e deve ser texto.
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

- Recusar criação de tickets em categorias inativas.
- Definir regras de atribuição e alteração de agente responsável.
- Definir operações de edição e exclusão de tickets, que ainda não têm rotas.
