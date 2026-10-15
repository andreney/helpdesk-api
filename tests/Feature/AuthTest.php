<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('usuário pode realizar login com credenciais válidas', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/v1/login', [
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
    $response = $this->postJson('/api/v1/login', [
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

    $loginResponse = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $loginResponse->assertOk();

    $token = $loginResponse->json('token');

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/tickets');

    $response
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('login exige email e senha', function () {
    $response = $this->postJson('/api/v1/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});

test('login rejeita email em formato inválido', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'email-invalido',
        'password' => 'password',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('login rejeita senha que não seja texto', function () {
    $response = $this->postJson('/api/v1/login', [
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

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'senha-incorreta',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Credenciais inválidas.',
        ]);
});

test('bloqueia requisições de login após exceder o limite do rate limit', function () {
    // 1 a 5: Executa 5 tentativas seguidas que atingem o limite máximo permitido
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/login', [
            'email' => 'inexistente@helpdesk.test',
            'password' => 'senha-incorreta',
        ]);
    }

    // 6ª tentativa: Deve ser bloqueada pelo middleware throttle
    $response = $this->postJson('/api/v1/login', [
        'email' => 'inexistente@helpdesk.test',
        'password' => 'senha-incorreta',
    ]);

    $response->assertStatus(429);
});

test('usuário autenticado pode realizar logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logout realizado com sucesso.',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('logout sem autenticação retorna 401', function () {
    $this->postJson('/api/v1/logout')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
});

test('logout sem autenticação e sem header Accept retorna 401 e não 500', function () {
    // Reproduz o cenário do Postman que gerava "Route [login] not defined"
    $this->post('/api/v1/logout')
        ->assertUnauthorized()
        ->assertExactJson([
            'message' => 'Não autenticado.',
        ]);
});

test('logout revoga apenas o token usado na requisição', function () {
    $user = User::factory()->create();
    $tokenAtual = $user->createToken('dispositivo-1')->plainTextToken;
    $user->createToken('dispositivo-2');

    $this
        ->withHeader('Authorization', 'Bearer '.$tokenAtual)
        ->postJson('/api/v1/logout')
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

    $token = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('token');

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/logout')
        ->assertOk();

    $this->app['auth']->forgetGuards();

    $this
        ->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/tickets')
        ->assertUnauthorized();
});
