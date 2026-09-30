<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Repositories\TicketCommentRepository;

class TicketCommentService
{
    public function __construct(
        private readonly TicketCommentRepository $ticketCommentRepository
    ) {}

    /**
     * Cria um comentário em um ticket.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(
        Ticket $ticket,
        User $user,
        array $data
    ): TicketComment {
        return $this->ticketCommentRepository->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);
    }
}
