<?php

/*
 * Mensagens de validação em português do Brasil.
 *
 * Cobre as regras usadas pela API. Qualquer chave ausente aqui cai no inglês
 * (APP_FALLBACK_LOCALE=en), então novas regras continuam funcionando.
 */

return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'date' => 'O campo :attribute não é uma data válida.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'enum' => 'O valor selecionado para :attribute é inválido.',
    'exists' => 'O valor selecionado para :attribute é inválido.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'O valor informado em :attribute já está em uso.',

    'custom' => [],

    'attributes' => [
        'category_id' => 'categoria',
        'comment' => 'comentário',
        'description' => 'descrição',
        'email' => 'e-mail',
        'name' => 'nome',
        'password' => 'senha',
        'priority' => 'prioridade',
        'status' => 'status',
        'title' => 'título',
    ],
];
