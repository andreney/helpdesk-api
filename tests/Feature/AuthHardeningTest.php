<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login devolve apenas os dados públicos do usuário, com o perfil em texto', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'role' => UserRole::ADMIN,
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('user.role', 'ADMIN');

    expect(array_keys($response->json('user')))->toBe(['id', 'name', 'email', 'role']);
});

test('limite de tentativas de login é por e-mail: outra conta não é bloqueada', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/login', [
            'email' => 'alvo@helpdesk.test',
            'password' => 'senha-incorreta',
        ]);
    }

    $this->postJson('/api/v1/login', [
        'email' => 'alvo@helpdesk.test',
        'password' => 'senha-incorreta',
    ])->assertStatus(429);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();
});

test('login com e-mail inexistente também compara um hash de referência', function () {
    Cache::forget('auth.fallback-hash');

    $this->postJson('/api/v1/login', [
        'email' => 'inexistente@helpdesk.test',
        'password' => 'qualquer-senha',
    ])->assertUnauthorized();

    expect(Cache::has('auth.fallback-hash'))->toBeTrue();
});
