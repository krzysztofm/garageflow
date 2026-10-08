<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => $this->department,
            'position' => $this->position,
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded(
                'manager',
                fn () => new UserResource($this->manager)
            ),
            'can_update' => $request->user()->can('update', $this->resource),
        ];
    }
}