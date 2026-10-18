# Endpoints — Helpdesk API

Base local: http://127.0.0.1:8000

Todas as rotas, exceto POST /api/v1/login, exigem um token Sanctum no cabeçalho:

    Authorization: Bearer SEU_TOKEN

## Resumo das rotas

| Método | Rota | Autenticação | Permissão |
|---|---|---|---|
| POST | /api/v1/login | Não | Credenciais válidas |
| POST | /api/v1/logout | Sim | Revoga o token usado na requisição |
| GET | /api/v1/categories | Sim | Todos os perfis (somente categorias ativas) |
| GET | /api/v1/tickets | Sim | USER vê os próprios; AGENT e ADMIN veem todos |
| POST | /api/v1/tickets | Sim | Todos os perfis |
| GET | /api/v1/tickets/{ticket} | Sim | USER vê o próprio; AGENT e ADMIN veem qualquer |
| PATCH | /api/v1/tickets/{ticket}/status | Sim | AGENT e ADMIN |
| POST | /api/v1/tickets/{ticket}/comments | Sim | USER no próprio ticket; AGENT e ADMIN em qualquer |

## POST /api/v1/login

Autentica o usuário e retorna um token Bearer.

Payload:

    {
      "email": "usuario@example.com",
      "password": "senha"
    }

Resposta 200:

    {
      "token": "TOKEN_EMITIDO",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "name": "Usuário",
        "email": "usuario@example.com",
        "role": "USER"
      }
    }

O campo `role` é o nome do perfil: `USER`, `AGENT` ou `ADMIN`.

Respostas de erro:

- 401: email ou senha incorretos.
- 422: email ou senha ausente ou inválido.
- 429: excesso de tentativas. O limite é de 5 por minuto para o mesmo e-mail e IP, e de 20 por minuto por IP.

Os tokens Bearer expiram após 60 minutos por padrão. Esse prazo pode ser alterado pela variável de ambiente `SANCTUM_EXPIRATION`, cujo valor é expresso em minutos. Depois da expiração, as rotas protegidas recusam o token e retornam 401; é necessário fazer login novamente para obter outro.

## POST /api/v1/logout

Encerra a sessão revogando somente o token Sanctum enviado no cabeçalho `Authorization`. Outros tokens do mesmo usuário permanecem válidos.

Resposta 200:

    {
      "message": "Logout realizado com sucesso."
    }

Resposta de erro:

- 401: token ausente ou inválido.

## GET /api/v1/categories

Lista as categorias ativas, em ordem alfabética. São as únicas aceitas em `category_id` na criação de tickets.

Resposta 200:

    {
      "data": [
        {
          "id": 1,
          "name": "Acesso e senhas",
          "description": "Contas, permissões e redefinição de senha"
        }
      ]
    }

Respostas de erro:

- 401: token ausente ou inválido.

## GET /api/v1/tickets

Lista tickets. USER recebe somente os tickets que abriu. AGENT e ADMIN recebem tickets de todos os usuários.

Resposta 200:

    {
      "data": [
        {
          "id": 1,
          "title": "Computador não inicia",
          "status": "OPEN"
        }
      ]
    }

Sem tickets, data é um array vazio.

Resposta de erro:

- 401: token ausente ou inválido.

## POST /api/v1/tickets

Cria um ticket para o usuário autenticado. O campo user_id não deve ser enviado; a API define o solicitante com base no token.

Payload:

    {
      "category_id": 1,
      "title": "Computador não inicia",
      "description": "O computador apresenta erro ao iniciar.",
      "priority": "HIGH"
    }

Regras atuais:

- category_id deve ser um inteiro correspondente a uma categoria **ativa** (veja `GET /api/v1/categories`).
- title é obrigatório, texto e limitado a 150 caracteres.
- description é obrigatório, texto e limitado a 5000 caracteres.
- priority aceita LOW, MEDIUM, HIGH ou URGENT.
- O novo ticket começa em OPEN.

Resposta 201: objeto do ticket dentro de data, com os dados da categoria e do solicitante.

Respostas de erro:

- 401: token ausente ou inválido.
- 422: campo obrigatório ou inválido.

## GET /api/v1/tickets/{ticket}

Retorna os dados de um ticket, incluindo comentários e autores.

Resposta 200: objeto do ticket dentro de data. O campo `allowed_transitions` lista os status para os quais o ticket pode evoluir (vazio quando CLOSED).

Respostas de erro:

- 401: token ausente ou inválido.
- 403: USER tenta acessar ticket de outra pessoa.
- 404: ticket inexistente.

## PATCH /api/v1/tickets/{ticket}/status

Altera o status do ticket. Apenas AGENT e ADMIN podem executar a operação.

Payload:

    {
      "status": "IN_PROGRESS"
    }

Transições permitidas:

| De | Para |
|---|---|
| OPEN | IN_PROGRESS |
| IN_PROGRESS | WAITING ou CLOSED |
| WAITING | IN_PROGRESS |
| CLOSED | Nenhum |

Ao mudar para CLOSED, closed_at é preenchido. Nas outras transições permitidas, permanece nulo.

Ao mudar para IN_PROGRESS um ticket que ainda não tem responsável, quem executou a ação passa a ser o responsável (`assigned_to`). Um responsável já definido é mantido.

A alteração é feita em transação, com o ticket bloqueado, então duas mudanças simultâneas não se sobrepõem.

Resposta 200: objeto do ticket atualizado dentro de `data` (mesmo formato de `GET /api/v1/tickets/{ticket}`), incluindo `allowed_transitions` com os próximos status possíveis.

Respostas de erro:

- 401: token ausente ou inválido.
- 403: perfil sem permissão (verificado antes da validação do corpo).
- 404: ticket inexistente.
- 422: status desconhecido ou transição não permitida.

## POST /api/v1/tickets/{ticket}/comments

Adiciona um comentário ao ticket. O autor é sempre o usuário autenticado e o ticket é determinado pela rota; user_id e ticket_id enviados no payload são ignorados.

Payload:

    {
      "comment": "Já reiniciei o computador, mas o problema continua."
    }

Resposta 201:

    {
      "data": {
        "id": 1,
        "comment": "Já reiniciei o computador, mas o problema continua.",
        "user": {
          "id": 1,
          "name": "Usuário"
        }
      }
    }

Respostas de erro:

- 401: token ausente ou inválido.
- 403: USER tenta comentar no ticket de outra pessoa.
- 404: ticket inexistente.
- 422: comentário ausente, não textual ou com mais de 5000 caracteres.

## Formato geral das respostas de erro

A API usa códigos HTTP para indicar o resultado. Em particular, a resposta 403 de autorização contém:

    {
      "message": "Você não tem permissão para realizar esta ação."
    }

As respostas 422 contêm os erros de validação, em português (`APP_LOCALE=pt_BR`), ou a mensagem da regra de negócio que recusou a transição.
