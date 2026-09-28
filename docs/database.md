# Banco de Dados — HelpDesk API

## 1. Visão geral

O HelpDesk API utiliza **MySQL** como banco de dados e possui quatro entidades principais de domínio:

| Entidade | Descrição resumida |
|---|---|
| `users` | Usuários da aplicação (solicitantes, agentes, administradores) |
| `categories` | Categorias usadas para classificar chamados |
| `tickets` | Chamados de suporte abertos pelos usuários |
| `ticket_comments` | Interações (comentários) registradas dentro de um ticket |

Além dessas, o Laravel mantém tabelas internas do framework (cache, jobs, sessions, migrations, etc.), que não fazem parte do domínio de negócio e por isso não são detalhadas neste documento.

A estrutura do banco é controlada exclusivamente pelas **migrations do Laravel** — ver seção [5](#5-origem-da-estrutura-e-governança).

---

## 2. Entidades

### 2.1 `users`

Tabela de usuários da aplicação.

| Campo | Tipo | Regra | Descrição |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto-incremento | Identificador do usuário |
| `name` | `VARCHAR(255)` | Obrigatório | Nome do usuário |
| `email` | `VARCHAR(255)` | Obrigatório, único | E-mail de acesso |
| `email_verified_at` | `TIMESTAMP` | Nullable | Data de verificação do e-mail |
| `password` | `VARCHAR(255)` | Obrigatório | Senha armazenada com hash (bcrypt) |
| `role` | `TINYINT UNSIGNED` | Obrigatório, default `1` | Perfil do usuário |
| `remember_token` | `VARCHAR(100)` | Nullable | Token de autenticação persistente ("lembrar-me") |
| `created_at` | `TIMESTAMP` | Automático | Data de criação |
| `updated_at` | `TIMESTAMP` | Automático | Data da última atualização |

**Valores de `role`:**

| Valor | Perfil | Descrição |
|---|---|---|
| `1` | `USER` | Solicitante — abre e acompanha chamados |
| `2` | `AGENT` | Agente — pode ser responsável por chamados |
| `3` | `ADMIN` | Administrador — acesso completo ao sistema |

> **Nota de modelagem:** o campo `role` não possui FK nesta primeira versão (ver seção 4.1). A aplicação é responsável por validar os valores permitidos.

**Índices recomendados:** `UNIQUE (email)`.

---

### 2.2 `categories`

Categorias utilizadas para classificar os chamados.

| Campo | Tipo | Regra | Descrição |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto-incremento | Identificador da categoria |
| `name` | `VARCHAR(100)` | Obrigatório | Nome da categoria |
| `description` | `VARCHAR(255)` | Nullable | Descrição da categoria |
| `active` | `BOOLEAN` | Obrigatório, default `true` | Indica se a categoria está disponível para uso |
| `created_at` | `TIMESTAMP` | Automático | Data de criação |
| `updated_at` | `TIMESTAMP` | Automático | Data da última atualização |

> **Regra de negócio:** uma categoria utilizada por tickets **não deve ser removida**. A estratégia é desativá-la (`active = false`), preservando o histórico dos chamados já registrados.

---

### 2.3 `tickets`

Entidade principal do sistema. Representa um chamado de suporte.

| Campo | Tipo | Regra | Descrição |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto-incremento | Identificador do ticket |
| `user_id` | `BIGINT UNSIGNED` | FK → `users.id`, obrigatório | Usuário que abriu o chamado |
| `category_id` | `BIGINT UNSIGNED` | FK → `categories.id`, obrigatório | Categoria do chamado |
| `assigned_to` | `BIGINT UNSIGNED` | FK → `users.id`, nullable | Usuário responsável pelo atendimento |
| `title` | `VARCHAR(150)` | Obrigatório | Título do chamado |
| `description` | `TEXT` | Obrigatório | Descrição do problema |
| `priority` | `VARCHAR(20)` | Obrigatório | Prioridade do chamado |
| `status` | `VARCHAR(20)` | Obrigatório | Status do chamado |
| `closed_at` | `TIMESTAMP` | Nullable | Data de encerramento |
| `created_at` | `TIMESTAMP` | Automático | Data de criação |
| `updated_at` | `TIMESTAMP` | Automático | Data da última atualização |

**Valores de `priority`:** `LOW` · `MEDIUM` · `HIGH` · `URGENT`

**Valores de `status`:** `OPEN` · `IN_PROGRESS` · `WAITING` · `CLOSED`

> **Notas de negócio:**
> - `assigned_to` pode ser nulo porque um ticket pode ser criado antes de receber um responsável.
> - A aplicação deve garantir que o usuário atribuído a `assigned_to` possua o perfil `AGENT` (essa validação não é feita no nível do banco).
> - `closed_at` deve ser preenchido no momento em que `status` transiciona para `CLOSED`.

**Índices recomendados:** `INDEX (user_id)`, `INDEX (category_id)`, `INDEX (assigned_to)`, `INDEX (status)` — os três primeiros aceleram os relacionamentos, o último é útil para filtros de listagem (ex.: "todos os tickets abertos").

---

### 2.4 `ticket_comments`

Registra as interações realizadas dentro de um ticket.

| Campo | Tipo | Regra | Descrição |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | PK, auto-incremento | Identificador do comentário |
| `ticket_id` | `BIGINT UNSIGNED` | FK → `tickets.id`, obrigatório | Ticket relacionado |
| `user_id` | `BIGINT UNSIGNED` | FK → `users.id`, obrigatório | Usuário que realizou o comentário |
| `comment` | `TEXT` | Obrigatório | Conteúdo do comentário |
| `created_at` | `TIMESTAMP` | Automático | Data de criação |
| `updated_at` | `TIMESTAMP` | Automático | Data da última atualização |

Um ticket pode possuir vários comentários, e um usuário pode realizar vários comentários.

**Índices recomendados:** `INDEX (ticket_id)`, `INDEX (user_id)`.

---

## 3. Relacionamentos

```text
users
 ├── 1:N → tickets           (tickets.user_id)
 ├── 1:N → tickets           (tickets.assigned_to)
 └── 1:N → ticket_comments   (ticket_comments.user_id)

categories
 └── 1:N → tickets           (tickets.category_id)

tickets
 └── 1:N → ticket_comments   (ticket_comments.ticket_id)
```

### 3.1 Regras de exclusão (`ON DELETE`)

| Relação | Comportamento | Motivo |
|---|---|---|
| `tickets.user_id` → `users.id` | `RESTRICT` | O solicitante não pode ser excluído enquanto possuir tickets, evitando perda de histórico. |
| `tickets.category_id` → `categories.id` | `RESTRICT` | A categoria não pode ser excluída enquanto associada a tickets; deve ser desativada em vez disso. |
| `tickets.assigned_to` → `users.id` | `SET NULL` | Se o responsável for removido, o ticket permanece, apenas ficando sem responsável. |
| `ticket_comments.ticket_id` → `tickets.id` | `CASCADE` | Comentários pertencem ao ticket e não têm utilidade isolada; são removidos junto com ele. |
| `ticket_comments.user_id` → `users.id` | `RESTRICT` | O autor do comentário não pode ser excluído enquanto houver comentários associados, preservando o histórico. |

---

## 4. Decisões de modelagem

### 4.1 `role` sem tabela própria

Nesta primeira versão, os perfis de usuário são representados por um `TINYINT UNSIGNED` em vez de uma tabela `roles` com FK. Isso mantém o MVP simples e evita a criação de uma entidade sem necessidade imediata. A aplicação é responsável por validar os valores permitidos.

**Trade-off:** essa abordagem é mais rápida de implementar, mas não garante integridade referencial no nível do banco. Se o sistema evoluir para suportar papéis customizáveis ou permissões mais granulares, recomenda-se migrar para uma tabela `roles` com FK em `users.role_id`.

### 4.2 `active` em categorias (soft state em vez de exclusão)

Categorias não são tratadas como registros descartáveis. Quando uma categoria deixa de ser utilizada, ela é desativada (`active = false`) em vez de excluída, preservando os tickets históricos associados a ela.

### 4.3 `assigned_to` separado de `user_id`

Os dois campos em `tickets` representam papéis diferentes e por isso ambos apontam para `users`, mas com responsabilidades distintas:

- **`user_id`** — quem abriu o ticket (o solicitante).
- **`assigned_to`** — quem está responsável pelo atendimento (o agente).

---

## 5. Origem da estrutura e governança

A estrutura do banco é controlada pelas **migrations do Laravel**, que são a fonte única de verdade do schema:

```bash
php artisan migrate
```

**Diretriz importante:** as tabelas não devem ser criadas ou alteradas manualmente no banco durante o desenvolvimento normal do projeto. Qualquer alteração de schema deve passar por uma nova migration, garantindo que o histórico de mudanças fique versionado e reprodutível em todos os ambientes.
