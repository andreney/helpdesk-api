<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário comum pode visualizar o próprio ticket', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id)
        ->assertJsonPath('data.user.id', $user->id);
});

test('usuário comum não pode visualizar ticket de outro usuário', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $otherUser = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response
        ->assertForbidden()
        ->assertJson([
            'message' => 'Você não tem permissão para realizar esta ação.',
        ]);
});

test('administrador e agente podem visualizar ticket de outro usuário', function (UserRole $role) {
    $viewer = User::factory()->create([
        'role' => $role,
    ]);

    $owner = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->for($owner, 'user')->create();

    Sanctum::actingAs($viewer);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->id)
        ->assertJsonPath('data.user.id', $owner->id);
})->with([
    'administrador' => UserRole::ADMIN,
    'agente' => UserRole::AGENT,
]);

test('usuário não autenticado não pode visualizar um ticket', function () {
    $ticket = Ticket::factory()->create();

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response->assertUnauthorized();
});

test('visualização do ticket inclui seus comentários e autores', function () {
    $owner = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->for($owner, 'user')->create();
    $comment = 'Estou analisando o problema.';

    $ticket->comments()->create([
        'user_id' => $agent->id,
        'comment' => $comment,
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.comments')
        ->assertJsonPath('data.comments.0.comment', $comment)
        ->assertJsonPath('data.comments.0.user.id', $agent->id);
});

test('retorna 404 ao visualizar ticket inexistente', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets/0');

    $response
        ->assertNotFound()
        ->assertJson([
            'message' => 'Ticket não encontrado.',
        ]);
});
