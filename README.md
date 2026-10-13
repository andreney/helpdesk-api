<div align="center">

# 🎫 Helpdesk API

**API REST para gerenciamento de chamados de suporte, construída com Laravel.**

Autenticação por tokens, controle de acesso por perfil, tickets, comentários e testes automatizados.

[![CI](https://img.shields.io/github/actions/workflow/status/andreney/helpdesk-api/ci.yml?branch=main&label=CI&logo=githubactions&logoColor=white)](https://github.com/andreney/helpdesk-api/actions)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/docs/sanctum)
[![Pest](https://img.shields.io/badge/Tests-Pest-F28D1A?logo=pestphp&logoColor=white)](https://pestphp.com/)
[![License](https://img.shields.io/github/license/andreney/helpdesk-api)](LICENSE)
[![Last commit](https://img.shields.io/github/last-commit/andreney/helpdesk-api)](https://github.com/andreney/helpdesk-api/commits/main)

[Funcionalidades](#-funcionalidades) •
[Instalação](#-instalação) •
[Uso rápido](#-uso-rápido) •
[Testes](#-testes) •
[Documentação](#-documentação)

</div>

---

## ✨ Funcionalidades

- 🔐 Login e autenticação de API com **Laravel Sanctum**
- 👥 Perfis de usuário: `ADMIN`, `AGENT` e `USER`
- 🎫 Criação, listagem e visualização de tickets
- 🛡️ Controle de acesso por perfil e por solicitante
- 🔄 Alteração de status com transições permitidas
- 💬 Comentários em tickets, com autorização por perfil
- ✅ Testes de integração com **Pest**

## 🧰 Tecnologias

| Camada         | Tecnologia                                      |
| -------------- | ----------------------------------------------- |
| Linguagem      | PHP 8.4+                                        |
| Framework      | Laravel 13                                      |
| Banco de dados | MySQL                                           |
| Autenticação   | Laravel Sanctum                                 |
| Testes         | Pest                                            |

## 📋 Pré-requisitos

- PHP 8.4 ou superior
- [Composer](https://getcomposer.org/)
- MySQL em execução e um banco de dados criado para o projeto

## 🚀 Instalação

**1. Clone o repositório e instale as dependências**

```bash
git clone https://github.com/andreney/helpdesk-api.git
cd helpdesk-api
composer install
```

**2. Configure o ambiente**

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com os dados do seu banco:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

**3. Execute as migrations**

```bash
php artisan migrate
```

> 💡 Se o projeto tiver seeders, rode `php artisan db:seed` para criar usuários de exemplo para cada perfil.

**4. Inicie o servidor**

```bash
php artisan serve
```

A API ficará disponível em **http://127.0.0.1:8000**.

## ⚡ Uso rápido

Exemplo do fluxo básico: autenticar e criar um ticket.

**Login**

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "usuario@exemplo.com", "password": "senha"}'
```

**Criar um ticket** (usando o token recebido)

```bash
curl -X POST http://127.0.0.1:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Erro ao acessar o sistema", "description": "Descrição do problema"}'
```

> Os caminhos e campos acima são ilustrativos. A lista completa está em [Endpoints da API](docs/api-endpoints.md).

## 👥 Perfis e permissões

| Perfil  | Visão geral do acesso                                      |
| ------- | ---------------------------------------------------------- |
| `USER`  | Cria e acompanha os próprios tickets                       |
| `AGENT` | Atende tickets, altera status e comenta                    |
| `ADMIN` | Acesso completo à gestão de tickets e usuários             |

> Detalhes e transições de status permitidas em [Regras de negócio](docs/business-rules.md).

## 🧪 Testes

Execute a suíte completa:

```bash
php artisan test --compact
```

## 📚 Documentação

| Documento                                          | Conteúdo                                   |
| -------------------------------------------------- | ------------------------------------------ |
| [Status do projeto](docs/project-status.md)        | Andamento e próximos passos                |
| [Arquitetura](docs/architecture.md)                | Estrutura e decisões técnicas              |
| [Regras de negócio](docs/business-rules.md)        | Perfis, permissões e fluxo de status       |
| [Banco de dados](docs/database.md)                 | Modelo de dados e relacionamentos          |
| [Endpoints da API](docs/api-endpoints.md)          | Rotas, parâmetros e exemplos               |

## 📄 Licença

Distribuído sob a licença **MIT**. Veja o arquivo [`LICENSE`](LICENSE) para mais detalhes.

---

<div align="center">

**Andreney Santos** — [LinkedIn](https://www.linkedin.com/in/andreney-laranjeira-dos-santos) — andreney@gmail.com

</div>
