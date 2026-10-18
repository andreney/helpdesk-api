<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('mensagens de validação são devolvidas em português', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/tickets', [])
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0', 'O campo título é obrigatório.')
        ->assertJsonPath('errors.category_id.0', 'O campo categoria é obrigatório.');
});
