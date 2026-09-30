<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository
{
    /**
     * Cria um novo ticket.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Ticket
    {
        $ticket = Ticket::create($data);

        return $ticket->load([
            'category',
            'user',
            'assignedTo',
        ]);
    }

    /**
     * Lista os tickets.
     *
     * @return Collection<int, Ticket>
     */
    public function getAll(): Collection
    {
        return Ticket::with([
            'category',
            'user',
            'assignedTo',
        ])->latest()->get();
    }

    /**
     * Busca um ticket pelo ID.
     */
    public function findById(int $id): ?Ticket
    {
        return Ticket::with([
            'category',
            'user',
            'assignedTo',
            'comments.user',
        ])->find($id);
    }

    /**
     * Atualiza o status de um ticket.
     */
    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        $ticket->update([
            'status' => $status,
        ]);

        return $ticket->refresh();
    }
}
