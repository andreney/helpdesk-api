# Regras de Negócio — HelpDesk API

## 1. Objetivo

O HelpDesk API disponibiliza uma API REST para gerenciamento de chamados de suporte técnico, permitindo controlar **usuários**, **categorias**, **tickets** e **comentários**.

A aplicação deve manter regras simples, claras e centralizadas, priorizando facilidade de manutenção e evolução.

> Para a estrutura de tabelas, tipos e relacionamentos no banco, consulte [`database.md`](database.md).

---

## 2. Usuários

- Todo usuário deve possuir **nome**, **e-mail**, **senha** e **perfil de acesso**.
- O e-mail deve ser **único**.
- A senha deve ser armazenada de forma segura, utilizando **hash**.
- O perfil é definido pelo campo `role`.
- Usuários inativos não devem realizar operações que exijam autenticação ativa.
- Um usuário pode estar relacionado a diversos tickets.
- Um usuário pode realizar diversos comentários em tickets.

---

## 3. Categorias

- Toda categoria deve possuir um **nome** (obrigatório, máximo de **100 caracteres**).
- A **descrição** é opcional.
- Toda categoria possui um indicador de **ativa/inativa**.
- Categorias inativas **não** podem ser usadas na criação de novos tickets.
- Uma categoria pode estar relacionada a diversos tickets.
- A exclusão de uma categoria com tickets relacionados deve ser evitada. Preferencialmente, a categoria deve ser **desativada**.

---

## 4. Tickets

- Todo ticket deve possuir:
  - um **usuário solicitante** (quem abriu o chamado);
  - uma **categoria**;
  - um **título** (obrigatório);
  - uma **descrição** (obrigatória);
  - um **status**;
  - uma **data de criação**.
- O ticket permanece sempre associado ao usuário que o abriu.
- A categoria escolhida deve estar **ativa no momento da criação**.
- Um ticket pode possuir diversos comentários.
- O histórico de comentários do ticket deve ser preservado.

---

## 5. Status do ticket

O ticket possui um ciclo de vida controlado por status.

| Status | Significado |
|---|---|
| **Aberto** | Ticket criado e aguardando atendimento |
| **Em andamento** | Ticket em processo de atendimento |
| **Resolvido** | Solução aplicada ao problema |
| **Fechado** | Atendimento encerrado |

### 5.1 Fluxo de transição

```text
Aberto ──► Em andamento ──► Resolvido ──► Fechado
```

### 5.2 Regras

- Todo ticket recém-criado inicia como **Aberto**.
- As transições permitidas são apenas as do fluxo acima:

| De | Para |
|---|---|
| Aberto | Em andamento |
| Em andamento | Resolvido |
| Resolvido | Fechado |

- Um ticket **Fechado** representa um atendimento finalizado e **não deve receber alterações comuns de atendimento**.
- Alterações de status devem respeitar as regras de permissão definidas para cada perfil (ver seção 10).

---

## 6. Comentários

- Todo comentário deve estar relacionado a um **ticket** e ao **usuário que o criou**.
- O **conteúdo** é obrigatório.
- O comentário possui **data de criação**.
- Comentários fazem parte do histórico do atendimento.
- A exclusão de comentários deve ser restrita ou evitada, para preservar o histórico do ticket.
- A listagem deve respeitar a **ordem cronológica** (data de criação).

---

## 7. Relacionamentos

```text
User
 ├── possui vários Tickets
 └── possui vários TicketComments

Category
 └── possui vários Tickets

Ticket
 ├── pertence a User
 ├── pertence a Category
 └── possui vários TicketComments

TicketComment
 ├── pertence a Ticket
 └── pertence a User
```

---

## 8. Integridade dos dados

- Não são permitidos tickets associados a usuários ou categorias inexistentes.
- Não são permitidos comentários associados a tickets ou usuários inexistentes.
- As chaves estrangeiras garantem a integridade dos relacionamentos.
- Dados obrigatórios devem ser validados **antes** da persistência.
- Regras de negócio **não** devem depender exclusivamente da validação do banco de dados.

---

## 9. Exclusão de dados

O sistema prioriza a **preservação do histórico de atendimento**. Como regra geral:

| Entidade | Diretriz |
|---|---|
| Usuários | Não excluir fisicamente se houver tickets relacionados, sem antes tratar as dependências |
| Categorias | Se utilizadas por tickets, preferir **desativar** em vez de excluir |
| Tickets | Devem preservar seu histórico |
| Comentários | Fazem parte do histórico; a exclusão deve ser restrita |

---

## 10. Permissões

O acesso às operações da API considera o **perfil do usuário autenticado**. As permissões devem ser **centralizadas**, evitando regras espalhadas pelos controllers.

De forma geral:

| Perfil | Escopo |
|---|---|
| Usuário comum | Consultar e operar os tickets aos quais possui acesso |
| Responsável pelo atendimento | Executar operações adicionais relacionadas ao tratamento dos tickets |
| Administrador | Permissões superiores para gerenciamento do sistema |

As permissões específicas podem ser evoluídas conforme novos requisitos forem adicionados.

---

## 11. Princípios gerais

### Arquitetura em camadas

| Camada | Responsabilidade |
|---|---|
| **Controller** | Camada HTTP (requisição e resposta) |
| **Service** | Regras de negócio |
| **Repository** | Acesso a dados, quando necessário |
| **Model** | Relacionamentos e representação das entidades |

### Diretrizes

- Validar a entrada antes de executar a regra de negócio.
- Evitar duplicação de regras.
- Evitar regras de negócio diretamente dentro das migrations.
- Preservar o histórico de atendimento.
- Priorizar código simples e fácil de manter.
- Toda nova regra de negócio relevante deve ser documentada neste arquivo.

---

## 12. Evolução

Este documento representa as regras atualmente definidas para o **MVP** do HelpDesk API.

Itens como os abaixo podem ser adicionados posteriormente e **não fazem parte do escopo inicial**:

- autenticação e autorização avançadas;
- atribuição de tickets a atendentes;
- prioridades;
- SLA;
- anexos;
- notificações;
- histórico detalhado de alterações;
- métricas e dashboards.
