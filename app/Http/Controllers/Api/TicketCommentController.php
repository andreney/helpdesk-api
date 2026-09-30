<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Services\TicketCommentService;
use Illuminate\Http\JsonResponse;

class TicketCommentController extends Controller
{
    public function __construct(
        private readonly TicketCommentService $ticketCommentService
    ) {}

    /**
     * Cria um comentário em um ticket.
     */
    public function store(
        StoreTicketCommentRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $comment = $this->ticketCommentService->create(
            $ticket,
            $request->user(),
            $request->validated()
        );

        return (new TicketCommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }
}
