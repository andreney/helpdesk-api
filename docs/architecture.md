# Arquitetura — Helpdesk API

## Visão geral

Helpdesk API é uma API REST em Laravel para gerenciamento de tickets de suporte. A aplicação evolui de forma incremental: este documento descreve primeiro o que existe no código e identifica separadamente os próximos passos.

Documentos relacionados:

- [Regras de negócio](business-rules.md)
- [Banco de dados](database.md)
- [Endpoints da API](api-endpoints.md)
- [Status do projeto](project-status.md)

## Stack atual

| Tecnologia | Uso |
|---|---|
| PHP 8.4 | Linguagem |
| Laravel 13 | Framework |
| MySQL | Banco de dados da aplicação |
| Laravel Sanctum | Tokens de autenticação da API |
| Pest | Testes automatizados |
| Composer | Dependências PHP |

## Componentes implementados

- Rotas da API em routes/api.php.
- Autenticação por token Bearer com Sanctum.
- Form Requests para validação de criação de tickets, mudança de status e comentários.
- Controllers para autenticação, tickets e comentários.
- TicketPolicy para autorização de visualização, mudança de status e comentários.
- Services para criação, consulta, alteração de status e comentários.
- Repositories para persistência e consulta de tickets e comentários.
- Enums para perfis, prioridades e status.
- API Resources para formatar tickets e comentários.
- Testes Feature com Pest cobrindo os fluxos HTTP.

## Fluxo de uma requisição

    Cliente
      → rota e middleware auth:sanctum
      → validação por Form Request, quando aplicável
      → controller
      → autorização pela TicketPolicy, quando aplicável
      → service
      → repository e models
      → API Resource ou resposta JSON

A camada de Repository já está presente no projeto. Ela concentra operações de consulta e persistência usadas pelos Services. A regra de transição de status está no TicketService.

## Estrutura atual relevante

    app/
    ├── Enums/
    ├── Exceptions/
    ├── Http/
    │   ├── Controllers/Api/
    │   ├── Requests/
    │   └── Resources/
    ├── Models/
    ├── Policies/
    ├── Repositories/
    └── Services/

    database/
    ├── factories/
    ├── migrations/
    └── seeders/

    routes/api.php
    tests/
    ├── Feature/
    └── Unit/

## Persistência

As migrations são a fonte de verdade do schema. As entidades principais são users, categories, tickets e ticket_comments. Consulte [database.md](database.md) para campos, chaves estrangeiras e regras de exclusão.

Os valores de prioridade e status são representados por PHP Enums. Os status atuais são OPEN, IN_PROGRESS, WAITING e CLOSED.

## Autenticação e autorização

POST /api/v1/login emite tokens Bearer. As demais rotas da API ficam sob auth:sanctum.

TicketPolicy controla o acesso a tickets. USER lista e visualiza apenas tickets próprios e comenta apenas neles. AGENT e ADMIN podem listar, visualizar e comentar em qualquer ticket. Apenas AGENT e ADMIN podem alterar status.

A matriz detalhada está em [business-rules.md](business-rules.md).

## Testes

Os Feature Tests exercitam as rotas reais, autenticação, autorização, validação e persistência. Os testes estão organizados por fluxo dentro de tests/Feature. Unit Tests ficam disponíveis para lógica isolada que traga valor adicional; não é necessário duplicar em Unit uma regra já coberta adequadamente pelo fluxo da API.

Execute a suíte com:

    php artisan test --compact

## Próximas evoluções

- Aplicar a regra que impede usar categorias inativas na criação de tickets.
- Definir e implementar atribuição de tickets a agentes.
- Avaliar paginação e filtros para listagem.
- Criar workflow de CI para executar os testes em pushes e pull requests.
- Documentar alterações futuras neste arquivo e em business-rules.md quando afetarem a arquitetura ou o comportamento.
