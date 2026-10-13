<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Exceptions\InvalidTicketStatusTransitionException;
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
    public function getAll(User $user): Collection
    {
        return $this->ticketRepository->getAll($user);
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
        $currentStatus = $ticket->status;

        $allowedTransitions = [
            TicketStatus::OPEN->value => [
                TicketStatus::IN_PROGRESS->value,
            ],
            TicketStatus::IN_PROGRESS->value => [
                TicketStatus::WAITING->value,
                TicketStatus::CLOSED->value,
            ],
            TicketStatus::WAITING->value => [
                TicketStatus::IN_PROGRESS->value,
            ],
            TicketStatus::CLOSED->value => [],
        ];

        if (! in_array(
            $status,
            $allowedTransitions[$currentStatus->value],
            true
        )) {
            throw new InvalidTicketStatusTransitionException;
        }

        $closedAt = $status === TicketStatus::CLOSED->value
            ? now()
            : null;

        return $this->ticketRepository->updateStatus(
            $ticket,
            $status,
            $closedAt
        );
    }
}
