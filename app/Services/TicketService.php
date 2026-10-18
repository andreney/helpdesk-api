<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Exceptions\InvalidTicketStatusTransitionException;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     *
     * A leitura e a gravação acontecem em uma transação com a linha bloqueada,
     * então a transição é sempre validada contra o status mais recente.
     * Ao iniciar o atendimento de um ticket sem responsável, quem iniciou assume o ticket.
     *
     * @throws InvalidTicketStatusTransitionException
     */
    public function updateStatus(Ticket $ticket, string $status, User $actor): Ticket
    {
        $target = TicketStatus::from($status);

        return DB::transaction(function () use ($ticket, $target, $actor): Ticket {
            $current = $this->ticketRepository->findForUpdate($ticket->id);

            if (! $current->status->canTransitionTo($target)) {
                throw new InvalidTicketStatusTransitionException;
            }

            $assignedTo = $current->assigned_to
                ?? ($target === TicketStatus::IN_PROGRESS ? $actor->id : null);

            return $this->ticketRepository->updateStatus(
                $current,
                $target,
                $target === TicketStatus::CLOSED ? now() : null,
                $assignedTo
            );
        });
    }
}
