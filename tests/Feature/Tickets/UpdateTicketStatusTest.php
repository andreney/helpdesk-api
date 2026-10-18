<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('agente pode iniciar o atendimento de um ticket aberto', function () {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::IN_PROGRESS->value]
    );

    $response->assertOk();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::IN_PROGRESS->value,
        'closed_at' => null,
    ]);
});

test('não permite fechar diretamente um ticket aberto', function () {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::CLOSED->value]
    );

    $response
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Transição de status do ticket não permitida.',
        ]);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::OPEN->value,
        'closed_at' => null,
    ]);
});

test('agente pode encerrar ticket em atendimento e registrar a data de encerramento', function () {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::IN_PROGRESS,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::CLOSED->value]
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.status', TicketStatus::CLOSED->value);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::CLOSED)
        ->and($ticket->closed_at)->not->toBeNull();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::CLOSED->value,
    ]);
});

test('permite transições de status válidas', function (
    TicketStatus $currentStatus,
    TicketStatus $newStatus
) {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => $currentStatus,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => $newStatus->value]
    );

    $response->assertOk();

    $updatedTicket = $ticket->fresh();

    expect($updatedTicket->status)->toBe($newStatus);

    if ($newStatus === TicketStatus::CLOSED) {
        expect($updatedTicket->closed_at)->not->toBeNull();
    } else {
        expect($updatedTicket->closed_at)->toBeNull();
    }
})->with([
    'OPEN para IN_PROGRESS' => [TicketStatus::OPEN, TicketStatus::IN_PROGRESS],
    'IN_PROGRESS para WAITING' => [TicketStatus::IN_PROGRESS, TicketStatus::WAITING],
    'IN_PROGRESS para CLOSED' => [TicketStatus::IN_PROGRESS, TicketStatus::CLOSED],
    'WAITING para IN_PROGRESS' => [TicketStatus::WAITING, TicketStatus::IN_PROGRESS],
]);

test('bloqueia transições de status inválidas', function (
    TicketStatus $currentStatus,
    TicketStatus $newStatus
) {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => $currentStatus,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => $newStatus->value]
    );

    $response
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Transição de status do ticket não permitida.',
        ]);

    $ticket->refresh();

    expect($ticket->status)->toBe($currentStatus)
        ->and($ticket->closed_at)->toBeNull();
})->with([
    'OPEN para WAITING' => [TicketStatus::OPEN, TicketStatus::WAITING],
    'IN_PROGRESS para OPEN' => [TicketStatus::IN_PROGRESS, TicketStatus::OPEN],
    'WAITING para OPEN' => [TicketStatus::WAITING, TicketStatus::OPEN],
    'WAITING para CLOSED' => [TicketStatus::WAITING, TicketStatus::CLOSED],
    'CLOSED para OPEN' => [TicketStatus::CLOSED, TicketStatus::OPEN],
    'CLOSED para IN_PROGRESS' => [TicketStatus::CLOSED, TicketStatus::IN_PROGRESS],
    'CLOSED para WAITING' => [TicketStatus::CLOSED, TicketStatus::WAITING],
]);

test('usuário comum não pode alterar o status de um ticket', function () {
    $user = User::factory()->create([
        'role' => UserRole::USER,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::IN_PROGRESS->value]
    );

    $response
        ->assertForbidden()
        ->assertJson([
            'message' => 'Você não tem permissão para realizar esta ação.',
        ]);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::OPEN->value,
        'closed_at' => null,
    ]);
});

test('administrador pode alterar o status de um ticket', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::IN_PROGRESS->value]
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.status', TicketStatus::IN_PROGRESS->value);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::IN_PROGRESS->value,
        'closed_at' => null,
    ]);
});

test('usuário não autenticado não pode alterar o status de um ticket', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::IN_PROGRESS->value]
    );

    $response->assertUnauthorized();

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::OPEN->value,
        'closed_at' => null,
    ]);
});

test('não permite atualizar ticket com status inválido', function () {
    $agent = User::factory()->create([
        'role' => UserRole::AGENT,
    ]);

    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::OPEN,
        'closed_at' => null,
    ]);

    Sanctum::actingAs($agent);

    $response = $this->patchJson(
        "/api/v1/tickets/{$ticket->id}/status",
        ['status' => 'DONE']
    );

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => TicketStatus::OPEN->value,
        'closed_at' => null,
    ]);
});
