<?php

use App\Enums\TicketPriority;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('não permite criar ticket em categoria inativa', function () {
    $category = Category::factory()->create(['active' => false]);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');

    $this->assertDatabaseCount('tickets', 0);
});

test('descrição do ticket respeita o limite de 5000 caracteres', function () {
    $category = Category::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $payload = fn (string $description) => [
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => $description,
        'priority' => TicketPriority::LOW->value,
    ];

    $this->postJson('/api/v1/tickets', $payload(str_repeat('a', 5001)))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');

    $this->postJson('/api/v1/tickets', $payload(str_repeat('a', 5000)))
        ->assertCreated();
});

test('comentário respeita o limite de 5000 caracteres', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/tickets/{$ticket->id}/comments", [
        'comment' => str_repeat('a', 5001),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');

    $this->postJson("/api/v1/tickets/{$ticket->id}/comments", [
        'comment' => str_repeat('a', 5000),
    ])
        ->assertCreated();
});
