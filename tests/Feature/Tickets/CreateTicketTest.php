<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário autenticado pode criar um ticket', function () {
    $user = User::factory()->create();

    $category = Category::query()->create([
        'name' => 'Suporte Técnico',
        'description' => 'Suporte de hardware e software',
        'active' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.title', 'Computador não inicia')
        ->assertJsonPath('data.status', TicketStatus::OPEN->value);

    $this->assertDatabaseHas('tickets', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'status' => TicketStatus::OPEN->value,
    ]);
});

test('usuário não autenticado não pode criar um ticket', function () {
    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => 1,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response->assertUnauthorized();
});

test('não permite criar ticket sem os campos obrigatórios', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'category_id',
            'title',
            'description',
            'priority',
        ]);

    $this->assertDatabaseCount('tickets', 0);
});

test('não permite criar ticket com categoria inexistente', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => 999999,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');

    $this->assertDatabaseCount('tickets', 0);
});

test('não permite criar ticket com category_id que não seja inteiro', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => 'categoria-invalida',
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('category_id');

    $this->assertDatabaseCount('tickets', 0);
});

test('não permite criar ticket com prioridade inválida', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => 'CRITICAL',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('priority');

    $this->assertDatabaseCount('tickets', 0);
});

test('não permite criar ticket com título maior que 150 caracteres', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => str_repeat('a', 151),
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');

    $this->assertDatabaseCount('tickets', 0);
});

test('permite criar ticket com título de 150 caracteres', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $title = str_repeat('a', 150);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => $title,
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.title', $title);

    $this->assertDatabaseHas('tickets', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => $title,
    ]);
});

test('não permite criar ticket com título que não seja texto', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => 123,
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');

    $this->assertDatabaseCount('tickets', 0);
});

test('não permite criar ticket com descrição que não seja texto', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => 123,
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');

    $this->assertDatabaseCount('tickets', 0);
});

test('criação de ticket ignora user_id enviado no payload', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/tickets', [
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
        'description' => 'O computador apresenta erro ao iniciar.',
        'priority' => TicketPriority::HIGH->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.id', $user->id);

    $this->assertDatabaseHas('tickets', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Computador não inicia',
    ]);

    $this->assertDatabaseMissing('tickets', [
        'user_id' => $otherUser->id,
        'title' => 'Computador não inicia',
    ]);
});
