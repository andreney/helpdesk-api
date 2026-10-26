<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsAdmin(): User
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    Sanctum::actingAs($admin);

    return $admin;
}

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

test('lista é paginada em 15 itens por padrão', function () {
    Ticket::factory()->count(20)->create();
    actingAsAdmin();

    $this->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.last_page', 2);
});

test('per_page e page controlam a janela de resultados', function () {
    Ticket::factory()->count(25)->create();
    actingAsAdmin();

    $this->getJson('/api/v1/tickets?per_page=10&page=3')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 3);
});

test('rejeita per_page fora do limite e status desconhecido', function (string $query, string $field) {
    actingAsAdmin();

    $this->getJson("/api/v1/tickets?{$query}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'per_page acima de 100' => ['per_page=101', 'per_page'],
    'per_page zero' => ['per_page=0', 'per_page'],
    'status inválido' => ['status=DONE', 'status'],
]);

test('filtra por status', function () {
    Ticket::factory()->count(2)->create(['status' => TicketStatus::OPEN]);
    Ticket::factory()->create(['status' => TicketStatus::CLOSED, 'closed_at' => now()]);
    actingAsAdmin();

    $this->getJson('/api/v1/tickets?status=CLOSED')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);
});

test('busca por título, sem diferenciar maiúsculas', function () {
    Ticket::factory()->create(['title' => 'Impressora quebrada']);
    Ticket::factory()->create(['title' => 'VPN lenta']);
    actingAsAdmin();

    $this->getJson('/api/v1/tickets?search=IMPRESSORA')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Impressora quebrada');
});

test('mine=1 restringe ADMIN aos próprios tickets, e USER sempre vê só os seus', function () {
    $admin = actingAsAdmin();
    Ticket::factory()->count(2)->create(['user_id' => $admin->id]);
    Ticket::factory()->create();

    $this->getJson('/api/v1/tickets?mine=1')->assertOk()->assertJsonCount(2, 'data');

    $user = User::factory()->create(['role' => UserRole::USER]);
    Ticket::factory()->create(['user_id' => $user->id]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/tickets?mine=0')->assertOk()->assertJsonCount(1, 'data');
});

test('resumo conta tickets por status, incluindo status zerados', function () {
    Ticket::factory()->count(2)->create(['status' => TicketStatus::OPEN]);
    Ticket::factory()->create(['status' => TicketStatus::CLOSED, 'closed_at' => now()]);
    actingAsAdmin();

    $this->getJson('/api/v1/tickets/summary')
        ->assertOk()
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.by_status.OPEN', 2)
        ->assertJsonPath('data.by_status.WAITING', 0)
        ->assertJsonPath('data.by_status.CLOSED', 1);
});

test('resumo de usuário comum considera só os próprios tickets', function () {
    $user = User::factory()->create(['role' => UserRole::USER]);
    Ticket::factory()->create(['user_id' => $user->id, 'status' => TicketStatus::OPEN]);
    Ticket::factory()->count(3)->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/tickets/summary')
        ->assertOk()
        ->assertJsonPath('data.total', 1);
});

test('resumo exige autenticação', function () {
    $this->getJson('/api/v1/tickets/summary')->assertUnauthorized();
});
