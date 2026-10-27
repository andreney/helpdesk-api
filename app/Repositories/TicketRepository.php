<?php

namespace App\Repositories;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

    /**
     * Lista os tickets paginados, do mais recente ao mais antigo.
     *
     * @param  array{status?: ?string, search?: ?string, mine?: bool}  $filters
     */
    public function paginate(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->visibleTo($user, $filters['mine'] ?? false)
            ->with(['category', 'user', 'assignedTo']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        // O id desempata tickets criados no mesmo instante e mantém a paginação estável.
        return $query->latest()->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Conta os tickets por status.
     *
     * @return array{total: int, by_status: array<string, int>}
     */
    public function countByStatus(User $user, bool $mine = false): array
    {
        $counts = $this->visibleTo($user, $mine)
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];

        foreach (TicketStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return ['total' => array_sum($byStatus), 'by_status' => $byStatus];
    }

    /**
     * Restringe aos tickets que o usuário pode ver: USER só vê os próprios;
     * ADMIN e AGENT veem todos, ou só os próprios quando $mine é verdadeiro.
     *
     * @return Builder<Ticket>
     */
    private function visibleTo(User $user, bool $mine): Builder
    {
        $query = Ticket::query();

        if ($mine || $user->role === UserRole::USER) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
