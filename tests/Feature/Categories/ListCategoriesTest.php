<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('usuário autenticado lista apenas categorias ativas em ordem alfabética', function () {
    Category::factory()->create(['name' => 'Redes', 'active' => true]);
    Category::factory()->create(['name' => 'Acesso', 'active' => true]);
    Category::factory()->create(['name' => 'Legado', 'active' => false]);

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Acesso')
        ->assertJsonPath('data.1.name', 'Redes')
        ->assertJsonStructure(['data' => [['id', 'name', 'description']]]);
});

test('lista de categorias não expõe campos internos', function () {
    Category::factory()->create();

    Sanctum::actingAs(User::factory()->create());

    $category = $this->getJson('/api/v1/categories')->json('data.0');

    expect(array_keys($category))->toBe(['id', 'name', 'description']);
});

test('usuário não autenticado não pode listar categorias', function () {
    $this->getJson('/api/v1/categories')->assertUnauthorized();
});
