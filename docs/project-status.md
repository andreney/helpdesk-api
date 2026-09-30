# Helpdesk API: Status do Projeto

API de helpdesk construída com Laravel, autenticação via Sanctum e controle de acesso por perfil (roles) com Policies.

## Implementado

- Autenticação via Laravel Sanctum
- Usuários com roles: `ADMIN`, `AGENT` e `USER`
- CRUD inicial de Tickets
  - Criação de tickets
  - Listagem com regra por perfil
  - Visualização individual protegida por `TicketPolicy`
- Comentários de tickets
- Tratamento padronizado de erro 403 (`AccessDeniedHttpException`) em rotas de API

## Regras de acesso atuais

| Ação                     | ADMIN | AGENT | USER                  |
|--------------------------|:-----:|:-----:|:---------------------:|
| Criar ticket             |  Sim  |  Sim  | Sim                   |
| Listar tickets           | Todos | Todos | Apenas os seus        |
| Visualizar ticket        | Todos | Todos | Apenas os seus        |

> Um `USER` que tentar acessar o ticket de outra pessoa recebe **403**. Esse é o comportamento esperado da `TicketPolicy::view`.

## Tratamento de erros

O 403 é tratado em `bootstrap/app.php`. O Laravel converte `AuthorizationException` em `AccessDeniedHttpException` **antes** de chamar os callbacks de `render()`, então o handler deve capturar este último:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    );

    $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
        return response()->json([
            'message' => 'Você não tem permissão para realizar esta ação.',
        ], 403);
    });
})
```

Resposta esperada:

```json
{
    "message": "Você não tem permissão para realizar esta ação."
}
```

## Próximos passos

### 1. Autorização de alteração de status
- Permitir alterar o status apenas para `ADMIN` e `AGENT`
- Criar o método `updateStatus` na `TicketPolicy`
- Usar `Gate::authorize('updateStatus', $ticket)` no controller
- Validar as transições permitidas (ex.: `open` → `in_progress` → `resolved` → `closed`) em um Form Request ou Enum

### 2. Autorização complementar (lacunas a definir)
- **Atualizar ticket** (título/descrição): quem pode? (sugestão: dono enquanto o ticket estiver aberto, ADMIN sempre)
- **Excluir ticket**: restringir a `ADMIN` (ou usar `SoftDeletes`)
- **Comentários**: criar `CommentPolicy` garantindo que o `USER` só comente em tickets que pode visualizar
- **Atribuição de agente** (`assigned_to`): definir se entra no escopo

### 3. Testes automatizados
Usar Feature Tests (Pest ou PHPUnit) com `Sanctum::actingAs()`:
- `USER` visualiza o próprio ticket → 200
- `USER` visualiza ticket de outro → 403
- `AGENT` e `ADMIN` visualizam qualquer ticket → 200
- `USER` lista e vê apenas os seus tickets
- `USER` tenta alterar status → 403
- `AGENT`/`ADMIN` alteram status → 200
- Ticket inexistente → 404
- Requisição sem token → 401

### 4. Documentação dos endpoints
- Documentar rotas, parâmetros, exemplos de resposta e códigos de erro (401, 403, 404, 422)
- Opções: Scramble (gera OpenAPI automaticamente a partir do código), L5-Swagger ou coleção do Postman/Insomnia exportada

## Melhorias sugeridas

- Padronizar as respostas de erro também para 401, 404 e 422 no mesmo formato JSON
- Criar factories e seeders com um usuário de cada role para facilitar testes manuais
- Evitar comparação com `===` entre IDs de tipos diferentes; garantir os casts nos models
- Manter o cast `'role' => UserRole::class` no model `User`
- Filtrar a listagem por perfil no **service/query** (`where('user_id', ...)` para `USER`), sem depender apenas da Policy
- Adicionar paginação na listagem de tickets
