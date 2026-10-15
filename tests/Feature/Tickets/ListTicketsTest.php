<?php

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário comum lista somente os próprios tickets', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $otherUser = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ownTicket = Ticket::factory()->for($user, 'user')->create();
    Ticket::factory()->for($otherUser, 'user')->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/tickets');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTicket->id)
        ->assertJsonMissingPath('data.1');
});

test('administrador e agente listam tickets de todos os usuários', function (UserRole $role) {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $otherUser = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $firstTicket = Ticket::factory()->for($user, 'user')->create();
    $secondTicket = Ticket::factory()->for($otherUser, 'user')->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/tickets');

    $response->assertOk()->assertJsonCount(2, 'data');

    $returnedTicketIds = collect($response->json('data'))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    $expectedTicketIds = collect([$firstTicket->id, $secondTicket->id])
        ->sort()
        ->values()
        ->all();

    expect($returnedTicketIds)->toBe($expectedTicketIds);
})->with([
    'administrador' => UserRole::ADMIN,
    'agente' => UserRole::AGENT,
]);

test('usuário não autenticado não pode listar tickets', function () {
    $response = $this->getJson('/api/v1/tickets');

    $response->assertUnauthorized();
});
