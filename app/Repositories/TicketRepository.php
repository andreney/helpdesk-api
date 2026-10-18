<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonInterface;
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
    public function getAll(User $user): Collection
    {
        $query = Ticket::with([
            'category',
            'user',
            'assignedTo',
        ]);

        if ($user->role === UserRole::USER) {
            $query->where('user_id', $user->id);
        }

        return $query->latest()->get();
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
     * Busca o ticket bloqueando a linha até o fim da transação atual,
     * para que duas alterações simultâneas não se sobreponham.
     */
    public function findForUpdate(int $id): Ticket
    {
        return Ticket::query()->lockForUpdate()->findOrFail($id);
    }

    /**
     * Atualiza o status de um ticket, a data de encerramento e o responsável.
     */
    public function updateStatus(
        Ticket $ticket,
        TicketStatus $status,
        ?CarbonInterface $closedAt,
        ?int $assignedTo
    ): Ticket {
        $ticket->update([
            'status' => $status,
            'closed_at' => $closedAt,
            'assigned_to' => $assignedTo,
        ]);

        return $ticket->load([
            'category',
            'user',
            'assignedTo',
        ]);
    }
}
