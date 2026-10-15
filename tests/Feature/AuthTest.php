<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('usuário pode realizar login com credenciais válidas', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user',
        ])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonMissingPath('user.password')
        ->assertJsonMissingPath('user.remember_token');
});

test('usuário não pode realizar login com credenciais inválidas', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'inexistente@helpdesk.test',
        'password' => 'senha-incorreta',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Credenciais inválidas.',
        ]);
});

test('token de login permite acessar uma rota protegida', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $loginResponse = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertOk();

    $token = $loginResponse->json('token');

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/tickets');

    $response
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('login exige email e senha', function () {
    $response = $this->postJson('/api/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});

test('login rejeita email em formato inválido', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'email-invalido',
        'password' => 'password',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('login rejeita senha que não seja texto', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'usuario@helpdesk.test',
        'password' => 123,
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

test('login rejeita senha incorreta para usuário existente', function () {
    $user = User::factory()->create([
        'password' => Hash::make('senha-correta'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'senha-incorreta',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Credenciais inválidas.',
        ]);
});

test('usuário autenticado pode realizar logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logout realizado com sucesso.',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('logout sem autenticação retorna 401', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});

test('logout revoga apenas o token usado na requisição', function () {
    $user = User::factory()->create();
    $tokenAtual = $user->createToken('dispositivo-1')->plainTextToken;
    $user->createToken('dispositivo-2');

    $this
        ->withHeader('Authorization', 'Bearer '.$tokenAtual)
        ->postJson('/api/logout')
        ->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'dispositivo-2',
    ]);
});

test('token revogado no logout não acessa mais rotas protegidas', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('token');

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertOk();

    $this->app['auth']->forgetGuards();

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/tickets')
        ->assertUnauthorized();
});
