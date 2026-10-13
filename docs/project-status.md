# Helpdesk API: Status do Projeto

API REST de suporte construída com Laravel 13, PHP 8.4 e MySQL. A autenticação usa Laravel Sanctum; as permissões são controladas por policies.

## Implementado

- Login com emissão de token Bearer.
- Perfis USER, AGENT e ADMIN.
- Criação de tickets com validação de categoria existente, título, descrição e prioridade.
- Listagem: USER vê somente os próprios tickets; AGENT e ADMIN veem todos.
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
| POST | /api/login | Público |
| GET | /api/tickets | Autenticado |
| POST | /api/tickets | Autenticado |
| GET | /api/tickets/{id} | Autenticado e autorizado pela policy |
| PATCH | /api/tickets/{ticket}/status | ADMIN ou AGENT |
| POST | /api/tickets/{ticket}/comments | USER dono do ticket, ADMIN ou AGENT |

Payloads, exemplos e respostas estão em [api-endpoints.md](api-endpoints.md).

## Testes automatizados

A suíte usa Pest e cobre os fluxos HTTP de autenticação, criação/listagem/visualização de tickets, status e comentários.

Última execução registrada em 13/10/2026: 57 testes passaram, com 187 assertions.

Para executar novamente:

    php artisan test --compact

## Pontos ainda não implementados ou a decidir

- A regra definida de impedir tickets em categorias inativas ainda não está aplicada na validação de criação; hoje é verificado se a categoria existe.
- Edição e exclusão de tickets não possuem rotas.
- Não há endpoints de administração de categorias ou usuários.
- Atribuição de agente e filtros/paginação da listagem ainda não estão disponíveis.
- A suíte não tem workflow de CI no GitHub Actions.

## Documentos relacionados

- [Arquitetura](architecture.md)
- [Regras de negócio](business-rules.md)
- [Banco de dados](database.md)
- [Endpoints da API](api-endpoints.md)
