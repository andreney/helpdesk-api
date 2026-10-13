# Helpdesk API

API REST para gerenciamento de chamados de suporte, desenvolvida com Laravel. O projeto inclui autenticação por tokens, controle de acesso por perfil, tickets, comentários e testes automatizados.

## Funcionalidades

- Login e autenticação de API com Laravel Sanctum.
- Perfis de usuário: ADMIN, AGENT e USER.
- Criação, listagem e visualização de tickets.
- Controle de acesso por perfil e solicitante.
- Alteração de status com transições permitidas.
- Comentários em tickets com autorização por perfil.
- Testes de integração com Pest.

## Tecnologias

- PHP 8.4+
- Laravel 13
- MySQL
- Laravel Sanctum
- Pest

## Configuração local

1. Instale as dependências com Composer.
2. Copie .env.example para .env e configure as variáveis de banco de dados.
3. Gere a chave da aplicação:
   
       php artisan key:generate

4. Execute as migrations:

       php artisan migrate

5. Inicie o servidor local:

       php artisan serve

A API ficará disponível em http://127.0.0.1:8000.

## Testes

Execute a suíte completa com:

    php artisan test --compact

## Documentação

- [Status do projeto](docs/project-status.md)
- [Arquitetura](docs/architecture.md)
- [Regras de negócio](docs/business-rules.md)
- [Banco de dados](docs/database.md)
- [Endpoints da API](docs/api-endpoints.md)
