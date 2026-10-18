<?php

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeder cria um usuário por perfil, categorias e tickets em todos os status', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (UserRole::cases() as $role) {
        expect(User::where('role', $role->value)->count())->toBe(1);
    }

    expect(Category::where('active', true)->count())->toBeGreaterThan(0)
        ->and(Category::where('active', false)->count())->toBeGreaterThan(0);

    foreach (TicketStatus::cases() as $status) {
        expect(Ticket::where('status', $status->value)->count())->toBeGreaterThan(0);
    }
});

test('seeder pode ser executado mais de uma vez sem duplicar dados', function () {
    $this->seed(DatabaseSeeder::class);

    $totals = [User::count(), Category::count(), Ticket::count()];

    $this->seed(DatabaseSeeder::class);

    expect([User::count(), Category::count(), Ticket::count()])->toBe($totals);
});

test('usuários do seeder conseguem fazer login', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (['admin', 'agente', 'usuario'] as $account) {
        $this->postJson('/api/v1/login', [
            'email' => "{$account}@helpdesk.test",
            'password' => 'password',
        ])->assertOk();
    }
});

test('seeder de demonstração é ignorado em produção', function () {
    $this->app['env'] = 'production';

    // Chamado direto: o comando db:seed pede confirmação em produção e nem chegaria ao seeder.
    $this->app->make(DatabaseSeeder::class)->run();

    expect(User::count())->toBe(0);
});
