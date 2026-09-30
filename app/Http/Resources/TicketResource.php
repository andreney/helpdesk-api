<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transforma o Resource em um array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'assigned_to' => $this->assignedTo
                ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ]
                : null,
            'comments' => TicketCommentResource::collection(
                $this->whenLoaded('comments')
            ),
        ];
    }
}
