<?php

test('rota raiz informa que a API está online', function () {
    $this->getJson('/')
        ->assertOk()
        ->assertJson([
            'name' => 'Helpdesk API',
            'status' => 'online',
        ]);
});

test('rota de health check responde com sucesso', function () {
    $this->get('/up')->assertOk();
});
