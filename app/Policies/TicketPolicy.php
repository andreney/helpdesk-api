<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determina se o usuário pode listar tickets.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode visualizar o ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::AGENT) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determina se o usuário pode criar tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode atualizar o modelo.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode alterar o status do ticket.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return in_array($user->role, [
            UserRole::ADMIN,
            UserRole::AGENT,
        ], true);
    }

    /**
     * Determina se o usuário pode excluir o modelo.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode restaurar o modelo.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode excluir permanentemente o modelo.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode comentar no ticket.
     */
    public function createComment(User $user, Ticket $ticket): bool
    {
        if (in_array($user->role, [UserRole::ADMIN, UserRole::AGENT], true)) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }
}
