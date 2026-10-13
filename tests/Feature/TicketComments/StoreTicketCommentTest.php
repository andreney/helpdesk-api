<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário autenticado pode comentar no próprio ticket', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($user, 'user')->create();
    $comment = 'Já reiniciei o computador, mas o problema continua.';

    Sanctum::actingAs($user);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => $comment]
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.comment', $comment)
        ->assertJsonPath('data.user.id', $user->id);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'comment' => $comment,
    ]);
});

test('usuário comum não pode comentar no ticket de outra pessoa', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $otherUser = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($otherUser, 'user')->create();
    $comment = 'Comentário que não deve ser salvo.';

    Sanctum::actingAs($user);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => $comment]
    );

    $response->assertForbidden();

    $this->assertDatabaseMissing('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'comment' => $comment,
    ]);
});

test('administrador e agente podem comentar no ticket de outra pessoa', function (UserRole $role) {
    $authorizedUser = User::factory()->create([
        'role' => $role,
    ]);

    $owner = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($owner, 'user')->create();
    $comment = 'Estou verificando o chamado.';

    Sanctum::actingAs($authorizedUser);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => $comment]
    );

    $response
        ->assertCreated()
        ->assertJsonPath('data.comment', $comment)
        ->assertJsonPath('data.user.id', $authorizedUser->id);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $authorizedUser->id,
        'comment' => $comment,
    ]);
})->with([
    'administrador' => UserRole::ADMIN,
    'agente' => UserRole::AGENT,
]);

test('usuário não autenticado não pode comentar em um ticket', function () {
    $ticket = Ticket::factory()->create();

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => 'Comentário sem autenticação.']
    );

    $response->assertUnauthorized();

    $this->assertDatabaseCount('ticket_comments', 0);
});

test('não permite criar comentário vazio', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($user, 'user')->create();

    Sanctum::actingAs($user);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => '']
    );

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');

    $this->assertDatabaseCount('ticket_comments', 0);
});

test('não permite criar comentário quando comment não é texto', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($user, 'user')->create();

    Sanctum::actingAs($user);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        ['comment' => 123]
    );

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');

    $this->assertDatabaseCount('ticket_comments', 0);
});

test('criação de comentário ignora user_id e ticket_id enviados no payload', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $otherUser = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($user, 'user')->create();
    $otherTicket = Ticket::factory()->for($otherUser, 'user')->create();
    $comment = 'Comentário no ticket correto.';

    Sanctum::actingAs($user);

    $response = $this->postJson(
        "/api/tickets/{$ticket->id}/comments",
        [
            'user_id' => $otherUser->id,
            'ticket_id' => $otherTicket->id,
            'comment' => $comment,
        ]
    );

    $response->assertCreated();

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'comment' => $comment,
    ]);

    $this->assertDatabaseMissing('ticket_comments', [
        'ticket_id' => $otherTicket->id,
        'user_id' => $otherUser->id,
        'comment' => $comment,
    ]);
});
