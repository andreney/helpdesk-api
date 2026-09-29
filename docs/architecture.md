# Arquitetura — HelpDesk API

## 1. Visão geral

O **HelpDesk API** é uma API REST desenvolvida em PHP com o framework **Laravel**, para gerenciamento de chamados de suporte técnico. O projeto também serve como portfólio para demonstrar conhecimentos em PHP, Laravel, APIs REST, MySQL, modelagem relacional, migrations, validação, autenticação/autorização e testes automatizados.

A arquitetura é construída **de forma incremental**, acompanhando a evolução real do projeto. O princípio adotado é evitar complexidade antecipada: novas camadas e abstrações são adicionadas somente quando há necessidade funcional ou técnica concreta.

> **Regra deste documento:** seções marcadas como **(planejado)** descrevem a arquitetura pretendida para as próximas fases, não código que já existe. A documentação arquitetural não deve descrever funcionalidades que ainda não existem como se já estivessem implementadas.

Documentos relacionados:

- [`business-rules.md`](business-rules.md) — regras de negócio.
- [`database.md`](database.md) — estrutura do banco de dados.

---

## 2. Stack

| Tecnologia | Utilização |
|---|---|
| PHP 8.4+ | Linguagem principal |
| Laravel 13 | Framework da aplicação |
| MySQL | Banco de dados |
| Composer | Gerenciamento de dependências |
| Git / GitHub | Controle de versão / repositório remoto |
| Sanctum *(planejado)* | Autenticação via tokens de API |
| Pest ou PHPUnit *(planejado)* | Testes automatizados |

---

## 3. Estado atual do projeto

Neste momento o projeto está na fase inicial de estruturação. Já estão definidos:

- projeto Laravel e configuração do ambiente;
- banco MySQL e migrations;
- estrutura das entidades principais e seus relacionamentos;
- regras básicas de integridade referencial;
- documentação da estrutura do banco (`database.md`) e das regras de negócio (`business-rules.md`).

**Arquitetura de código atual:**

```text
Laravel Application
       │
       ├── Configuration
       ├── Migrations
       └── Database
              ├── users
              ├── categories
              ├── tickets
              └── ticket_comments
```

Ainda **não existem** no código camadas de Service, Repository, Policy ou outras abstrações — elas serão introduzidas conforme a implementação da API avançar (ver seção 7, arquitetura planejada).

---

## 4. Banco de dados

O MySQL é responsável pela persistência dos dados. A estrutura é controlada exclusivamente pelas **Laravel Migrations**, fonte de verdade do schema:

```bash
php artisan migrate
```

Tabelas não devem ser criadas ou alteradas manualmente no banco durante o desenvolvimento normal do projeto. Detalhes de campos, tipos e relacionamentos estão em [`database.md`](database.md); aqui ficam apenas os pontos relevantes para decisões de arquitetura.

### 4.1 Entidades e relacionamentos

```text
users
 ├── 1:N → tickets (user_id)
 ├── 1:N → tickets (assigned_to)
 └── 1:N → ticket_comments

categories
 └── 1:N → tickets

tickets
 └── 1:N → ticket_comments
```

| Relação | `ON DELETE` | Objetivo |
|---|---|---|
| `tickets.user_id` → `users.id` | `RESTRICT` | Preservar histórico do solicitante |
| `tickets.category_id` → `categories.id` | `RESTRICT` | Preservar histórico da categoria |
| `tickets.assigned_to` → `users.id` | `SET NULL` | Ticket sobrevive à remoção do responsável |
| `ticket_comments.ticket_id` → `tickets.id` | `CASCADE` | Comentário não existe sem o ticket |
| `ticket_comments.user_id` → `users.id` | `RESTRICT` | Preservar histórico do autor do comentário |

### 4.2 Valores controlados

**Status do ticket:** `OPEN` · `IN_PROGRESS` · `WAITING` · `CLOSED`
**Prioridade do ticket:** `LOW` · `MEDIUM` · `HIGH` · `URGENT`

Esses valores fazem parte da estrutura da tabela `tickets`. Durante a implementação da camada de domínio, será avaliado o uso de **PHP Enums** para centralizá-los na aplicação (ver seção 8.1).

> ⚠️ Ver seção 13 — há uma divergência entre os nomes de status usados aqui (`OPEN`/`WAITING`/...) e os usados em `business-rules.md` (Aberto/Resolvido/...), ainda não resolvida.

---

## 5. Integridade dos dados

A integridade é garantida em dois níveis, progressivamente:

| Nível | Mecanismos |
|---|---|
| **Banco de dados** | Primary Keys, Foreign Keys, `NOT NULL`, valores padrão, regras de exclusão, tipos adequados |
| **Aplicação** *(planejado)* | Validação de entrada, regras de negócio, autorização, validação de relacionamentos, controle de status e de perfis |

Regras de negócio não devem depender exclusivamente da validação do banco.

---

## 6. `role` sem tabela própria

Os perfis de usuário (`USER = 1`, `AGENT = 2`, `ADMIN = 3`) são representados por `TINYINT UNSIGNED` em `users.role`, sem uma tabela `roles` nesta primeira versão. A aplicação é responsável por controlar os valores válidos. Mantém o MVP simples; se o projeto evoluir para papéis customizáveis, é candidato a virar uma tabela com FK.

---

## 7. Arquitetura planejada (camadas)

> Esta seção descreve a arquitetura **pretendida** para quando a API for implementada — nada aqui existe no código ainda.

```text
HTTP Request
      │
      ▼
Route
      │
      ▼
FormRequest        (valida entrada)
      │
      ▼
Controller
      │
      ▼
Policy              (autoriza)
      │
      ▼
Service              (regras de negócio)
      │
      ▼
Repository            (opcional — só quando necessário)
      │
      ▼
Model → MySQL
      │
      ▼
API Resource → Response JSON
```

### 7.1 Responsabilidades por camada

| Camada | Responsabilidade | O que **não** deve fazer |
|---|---|---|
| **Route** | Mapear endpoints e middlewares | Conter lógica |
| **FormRequest** | Validar formato/obrigatoriedade da entrada | Regras que dependem do estado do banco |
| **Controller** | Receber requisição, chamar Service, devolver resposta | Regras de negócio, queries |
| **Policy** | Autorização por perfil/recurso, centralizada | Regras de fluxo de negócio |
| **Service** | Regras de negócio (criação de ticket, transição de status, etc.) | Conhecer HTTP (`Request`/`Response`) |
| **Repository** | Consultas/persistência que justifiquem isolamento | Regras de negócio |
| **Model** | Relacionamentos, casts, atributos de persistência | Regras de negócio complexas |
| **API Resource** | Formatar a saída JSON | Consultas ou lógica |

**Repository é opcional por padrão.** Para manter o MVP simples, os Services usam Eloquent diretamente; um Repository só é criado quando houver consulta complexa ou necessidade real de isolamento (ex.: listagem de tickets com muitos filtros) — a camada não é criada apenas por convenção arquitetural.

### 7.2 Estrutura de diretórios planejada

```text
app/
├── Enums/
│   ├── UserRole.php
│   ├── TicketPriority.php
│   └── TicketStatus.php
├── Exceptions/
│   ├── InactiveCategoryException.php
│   └── InvalidStatusTransitionException.php
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Requests/
│   └── Resources/
├── Models/
│   ├── User.php
│   ├── Category.php
│   ├── Ticket.php
│   └── TicketComment.php
├── Policies/
├── Repositories/        # criado apenas quando necessário
└── Services/

database/
├── factories/
├── migrations/
└── seeders/

routes/api.php

tests/
├── Feature/
└── Unit/

docs/
├── architecture.md
├── business-rules.md
└── database.md
```

Diretórios ou classes não devem ser criados antecipadamente apenas para preencher essa estrutura — a organização acompanha as funcionalidades realmente implementadas.

---

## 8. Exemplos ilustrativos de implementação *(planejado)*

Os trechos abaixo são **exemplos de como as camadas devem se comportar** quando implementadas — não representam código já existente no projeto.

### 8.1 Enums para valores controlados

```php
// app/Enums/UserRole.php
enum UserRole: int
{
    case USER  = 1;
    case AGENT = 2;
    case ADMIN = 3;
}

// app/Enums/TicketStatus.php
enum TicketStatus: string
{
    case OPEN        = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case WAITING     = 'WAITING';
    case CLOSED      = 'CLOSED';
}
```

### 8.2 Model com relacionamentos

```php
// app/Models/Ticket.php
class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'assigned_to',
        'title', 'description', 'priority', 'status', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority'  => TicketPriority::class,
            'status'    => TicketStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo  // quem abriu o chamado
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo   // responsável pelo atendimento
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->oldest();
    }
}
```

### 8.3 Fluxo de exemplo: criar ticket

```text
POST /api/v1/tickets
  │
  ├─ 1. StoreTicketRequest       → valida title, description, category_id, priority
  ├─ 2. TicketController@store   → autoriza (TicketPolicy) e chama o Service
  ├─ 3. TicketService::create()  → categoria deve existir e estar ativa;
  │                                 status inicial = OPEN; user_id = usuário autenticado
  ├─ 4. Ticket::create()         → persiste
  └─ 5. TicketResource           → resposta 201 com JSON
```

```php
// app/Services/TicketService.php
public function create(User $requester, array $data): Ticket
{
    $category = Category::findOrFail($data['category_id']);

    if (! $category->active) {
        throw new InactiveCategoryException();
    }

    return Ticket::create([
        ...$data,
        'user_id' => $requester->id,
        'status'  => TicketStatus::OPEN,
    ]);
}
```

### 8.4 Transição de status

```php
// app/Enums/TicketStatus.php
public function allowedTransitions(): array
{
    return match ($this) {
        self::OPEN        => [self::IN_PROGRESS],
        self::IN_PROGRESS => [self::WAITING, self::CLOSED], // ajustar conforme fluxo final
        self::WAITING     => [self::IN_PROGRESS, self::CLOSED],
        self::CLOSED      => [],
    };
}
```

```php
// app/Services/TicketService.php
public function changeStatus(Ticket $ticket, TicketStatus $next): Ticket
{
    if ($ticket->status === TicketStatus::CLOSED) {
        throw new ClosedTicketException();
    }

    if (! in_array($next, $ticket->status->allowedTransitions(), true)) {
        throw new InvalidStatusTransitionException();
    }

    $ticket->update([
        'status'    => $next,
        'closed_at' => $next === TicketStatus::CLOSED ? now() : null,
    ]);

    return $ticket;
}
```

> As transições acima são um exemplo provisório — dependem da definição do ciclo de status (ver seção 13, item 1).

---

## 9. Validação *(planejado)*

Dois níveis, com responsabilidades distintas:

| Nível | Onde | Exemplos |
|---|---|---|
| **Entrada (formato)** | `FormRequest` | campo obrigatório, tamanho máximo, `exists:categories,id`, enum válido |
| **Negócio (estado)** | `Service` | categoria ativa, transição de status permitida, ticket não fechado |

Exemplos futuros de `FormRequest`: `StoreTicketRequest`, `UpdateTicketRequest`, `StoreTicketCommentRequest`.

---

## 10. Autenticação e autorização *(planejado)*

A API deve possuir autenticação para proteger os recursos, e autorização considerando os perfis de `users.role`, **centralizada em Policies** — evitando regras espalhadas pelos controllers.

| Perfil | Escopo geral |
|---|---|
| `USER` | Criar e acompanhar seus próprios tickets |
| `AGENT` | Atender tickets atribuídos |
| `ADMIN` | Administrar categorias, usuários e tickets |

```php
// app/Policies/TicketPolicy.php — exemplo
public function view(User $user, Ticket $ticket): bool
{
    return $user->role === UserRole::ADMIN
        || $user->role === UserRole::AGENT
        || $ticket->user_id === $user->id;
}
```

As regras específicas por endpoint serão refinadas conforme forem implementadas.

---

## 11. Design da API *(planejado)*

### 11.1 Convenções

- Prefixo e versionamento: `/api/v1`.
- Recursos no plural, em inglês (`/tickets`, `/categories`).
- Respostas formatadas por API Resources.
- Paginação nas listagens.
- Códigos HTTP: `200`, `201`, `204`, `401`, `403`, `404`, `422` para violação de regra de negócio.

### 11.2 Endpoints previstos

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/auth/login` | Autenticação e emissão de token |
| `GET` | `/tickets` | Lista tickets (paginado, com filtros) |
| `POST` | `/tickets` | Abre um ticket |
| `GET` | `/tickets/{ticket}` | Detalha um ticket |
| `PATCH` | `/tickets/{ticket}` | Atualiza dados do ticket |
| `PATCH` | `/tickets/{ticket}/status` | Altera o status |
| `GET` | `/tickets/{ticket}/comments` | Lista comentários (ordem cronológica) |
| `POST` | `/tickets/{ticket}/comments` | Adiciona comentário |
| `GET` \| `POST` \| `PATCH` | `/categories` | Lista, cria e atualiza categorias (inclui ativar/desativar) |
| `GET` | `/users` | Lista usuários (admin) |

> Não há endpoint `DELETE` para categorias, tickets e comentários — reflete a diretriz de preservar histórico (categorias são desativadas, comentários não são excluídos).

### 11.3 Tratamento de erros

Exceções de negócio (`InactiveCategoryException`, `InvalidStatusTransitionException`, `ClosedTicketException`) devem ser convertidas em respostas JSON padronizadas no handler global, mantendo os Controllers limpos:

```json
{
  "message": "Não é possível criar tickets em uma categoria inativa.",
  "code": "INACTIVE_CATEGORY"
}
```

---

## 12. Testes *(planejado)*

```text
tests/
├── Feature/   → fluxos HTTP completos
└── Unit/      → regras isoladas que justifiquem teste unitário
```

Cenários prioritários:

- Ticket nasce com status `OPEN`.
- Categoria inativa não aceita novos tickets.
- Transições de status inválidas são rejeitadas.
- Ticket fechado não recebe alterações comuns.
- `USER` não acessa tickets de outros usuários.
- Comentários são listados em ordem de criação.
- Usuário/categoria com dependências não podem ser excluídos (`RESTRICT`).

---

## 13. Pontos em aberto

Itens que dependem de decisão e podem alterar este documento e os demais (`business-rules.md`, `database.md`):

1. **Ciclo de status**: `business-rules.md` prevê Aberto → Em andamento → Resolvido → Fechado; `database.md` define `OPEN`, `IN_PROGRESS`, `WAITING`, `CLOSED`. As transições da seção 8.4 são provisórias até essa definição.
2. **Usuário inativo**: as regras de negócio citam usuários inativos, mas `users` não possui campo de ativo/inativo.
3. **Escopo do MVP**: `priority` e `assigned_to` já existem no banco, mas estão listados como fora do escopo inicial em `business-rules.md`.
4. **Autenticação**: Sanctum é uma sugestão, ainda não definida como requisito.

---

## 14. Princípios adotados

| Princípio | Aplicação |
|---|---|
| **KISS** | Manter as soluções simples enquanto forem suficientes para o problema |
| **SRP** | Cada classe possui uma responsabilidade clara |
| **Separation of Concerns** | Separar HTTP, negócio e persistência quando houver necessidade |
| **DRY** | Evitar duplicação desnecessária |
| **SOLID** | Aplicado de forma pragmática — sem abstrações criadas apenas para demonstrar conhecimento de padrões |

---

## 15. Evolução da arquitetura

```text
FASE 1  Projeto Laravel → Migrations → Banco de dados        ✅ concluído
FASE 2  Models → Relacionamentos
FASE 3  Requests / Validation → Controllers → Rotas
FASE 4  Services → Regras de negócio
FASE 5  Repositories → Consultas/persistência que precisem de isolamento
FASE 6  Autenticação / Autorização
FASE 7  Testes
FASE 8  Documentação da API
```

A ordem pode ser ajustada conforme a implementação revelar necessidades diferentes.

---

## 16. Decisões arquiteturais — resumo

- **Banco controlado por migrations**, fonte de verdade da estrutura.
- **`role` sem tabela própria** nesta primeira versão (seção 6).
- **Categorias desativáveis** em vez de removidas, preservando histórico.
- **`assigned_to` separado de `user_id`**: solicitante e responsável são papéis diferentes.
- **Arquitetura incremental**: Services, Repositories, Policies e outras abstrações só são criados quando há necessidade concreta — não antecipadamente.

---

## 17. Objetivo

A arquitetura do HelpDesk API busca manter o projeto **simples, organizado, testável, manutenível e evolutivo**, acompanhando o código real do projeto. Novas decisões devem ser incorporadas a este documento conforme forem implementadas e validadas no código.
