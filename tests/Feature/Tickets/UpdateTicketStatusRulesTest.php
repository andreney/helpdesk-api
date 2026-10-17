<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário comum recebe 403 mesmo quando envia um status inválido', function () {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::OPEN]);

    Sanctum::actingAs(User::factory()->create(['role' => UserRole::USER]));

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", ['status' => 'DONE'])
        ->assertForbidden()
        ->assertJson([
            'message' => 'Você não tem permissão para realizar esta ação.',
        ]);
});

test('ao iniciar o atendimento, o ticket é atribuído a quem iniciou', function () {
    $agent = User::factory()->create(['role' => UserRole::AGENT]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'assigned_to' => null,
    ]);

    Sanctum::actingAs($agent);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", [
        'status' => TicketStatus::IN_PROGRESS->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.assigned_to.id', $agent->id);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'assigned_to' => $agent->id,
    ]);
});

test('mantém o responsável original ao retomar o atendimento', function () {
    $originalAgent = User::factory()->create(['role' => UserRole::AGENT]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::WAITING,
        'assigned_to' => $originalAgent->id,
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", [
        'status' => TicketStatus::IN_PROGRESS->value,
    ])->assertOk();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'assigned_to' => $originalAgent->id,
    ]);
});

test('transição inválida não altera o responsável nem o status', function () {
    $agent = User::factory()->create(['role' => UserRole::AGENT]);
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'assigned_to' => null,
    ]);

    Sanctum::actingAs($agent);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", [
        'status' => TicketStatus::CLOSED->value,
    ])->assertUnprocessable();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::OPEN->value,
        'assigned_to' => null,
    ]);
});

test('resposta de status informa as próximas transições permitidas', function () {
    $agent = User::factory()->create(['role' => UserRole::AGENT]);
    $ticket = Ticket::factory()->create(['status' => TicketStatus::OPEN]);

    Sanctum::actingAs($agent);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/status", [
        'status' => TicketStatus::IN_PROGRESS->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.allowed_transitions', ['WAITING', 'CLOSED']);
});

test('ticket encerrado não informa próximas transições', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'status' => TicketStatus::CLOSED,
        'closed_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.allowed_transitions', []);
});
