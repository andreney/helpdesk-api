<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Os testes de Feature usam o TestCase da aplicação (HTTP, banco, container).
| Cada arquivo habilita o RefreshDatabase com uses(RefreshDatabase::class),
| de modo que testes que não tocam no banco continuam rápidos.
| Os testes de Unit são PHP puro e não inicializam o framework.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');
