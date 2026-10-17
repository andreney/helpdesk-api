<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTicketStatusRequest extends FormRequest
{
    /**
     * Determine se o usuário pode realizar a requisição.
     */
    public function authorize(): bool
    {
        // A autorização roda antes da validação: quem não tem permissão recebe 403, não 422.
        return $this->user()?->can('updateStatus', $this->route('ticket')) ?? false;
    }

    /**
     * Obtenha as regras de validação aplicáveis ​​à solicitação.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(TicketStatus::class)],
        ];
    }
}
