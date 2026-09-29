<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Database\Eloquent\Collection;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository
    ) {}

    /**
     * Cria um novo ticket.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Ticket
    {
        return $this->ticketRepository->create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => TicketStatus::OPEN,
            'assigned_to' => null,
        ]);
    }

    /**
     * Lista os tickets.
     *
     * @return Collection<int, Ticket>
     */
    public function getAll(): Collection
    {
        return $this->ticketRepository->getAll();
    }

    /**
     * Busca um ticket pelo ID.
     */
    public function findById(int $id): ?Ticket
    {
        return $this->ticketRepository->findById($id);
    }

    /**
     * Atualiza o status de um ticket.
     */
    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        return $this->ticketRepository->updateStatus(
            $ticket,
            $status
        );
    }
}
