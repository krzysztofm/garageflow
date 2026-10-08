<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource(
                $this->whenLoaded('employee')
            ),
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'destination' => $this->destination,
            'description' => $this->description,
            'status' => $this->status,
            'can_update' => $request->user()->can(
                'update',
                $this->resource
            ),
        ];
    }
}