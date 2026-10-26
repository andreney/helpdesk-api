# Helpdesk API: Status do Projeto

API REST de suporte construída com Laravel 13, PHP 8.4 e MySQL. A autenticação usa Laravel Sanctum; as permissões são controladas por policies.

## Implementado

- Login com emissão de token Bearer.
- Perfis USER, AGENT e ADMIN.
- Criação de tickets com validação de categoria ativa, título, descrição e prioridade.
- Listagem de categorias ativas (`GET /api/v1/categories`).
- Atribuição automática do responsável ao iniciar o atendimento.
- Mensagens de validação em português (pt_BR).
- Seeder de demonstração com um usuário por perfil, categorias e tickets.
- Listagem paginada (15 por página, até 100) com filtros por status, busca e "meus tickets"; USER vê somente os próprios tickets e AGENT e ADMIN veem todos.
- Resumo de tickets por status (`GET /api/v1/tickets/summary`).
- Visualização: USER vê somente os próprios tickets; AGENT e ADMIN podem ver qualquer ticket.
- Alteração de status por AGENT e ADMIN, respeitando a matriz de transições.
- Comentários: USER comenta somente nos próprios tickets; AGENT e ADMIN podem comentar em qualquer ticket.
- Respostas JSON para criação, listagem, visualização e comentários.

## Status permitidos

| Status atual | Próximos status permitidos |
|---|---|
| OPEN | IN_PROGRESS |
| IN_PROGRESS | WAITING, CLOSED |
| WAITING | IN_PROGRESS |
| CLOSED | Nenhum |

Ao encerrar um ticket, closed_at é preenchido. Nas demais transições permitidas, permanece nulo.

## Rotas implementadas

| Método | Rota | Acesso |
|---|---|---|
| POST | /api/v1/login | Público |
| GET | /api/v1/categories | Autenticado |
| GET | /api/v1/tickets | Autenticado |
| GET | /api/v1/tickets/summary | Autenticado |
| POST | /api/v1/tickets | Autenticado |
| GET | /api/v1/tickets/{ticket} | Autenticado e autorizado pela policy |
| PATCH | /api/v1/tickets/{ticket}/status | ADMIN ou AGENT |
| POST | /api/v1/tickets/{ticket}/comments | USER dono do ticket, ADMIN ou AGENT |

Payloads, exemplos e respostas estão em [api-endpoints.md](api-endpoints.md).

## Testes automatizados

A suíte usa Pest e cobre os fluxos HTTP de autenticação, criação/listagem/visualização de tickets, status e comentários.

Para executar:

    php artisan test --compact

## Pontos ainda não implementados ou a decidir

- Edição e exclusão de tickets não possuem rotas.
- Não há endpoints de administração de categorias ou usuários.
- Reatribuição manual de agente ainda não está disponível.

## Documentos relacionados

- [Arquitetura](architecture.md)
- [Regras de negócio](business-rules.md)
- [Banco de dados](database.md)
- [Endpoints da API](api-endpoints.md)
