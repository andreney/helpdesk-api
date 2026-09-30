<?php

namespace App\Repositories;

use App\Models\TicketComment;

class TicketCommentRepository
{
    /**
     * Cria um novo comentário.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketComment
    {
        return TicketComment::create($data);
    }
}
