<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A permissão de listar é verificada pela policy (viewAny) no controller.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', new Enum(TicketStatus::class)],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mine' => ['sometimes', 'boolean'],
        ];
    }
}
