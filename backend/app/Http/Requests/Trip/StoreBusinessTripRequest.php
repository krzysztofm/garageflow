<?php

namespace App\Http\Requests\Trip;

use App\Models\BusinessTrip;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BusinessTrip::class);
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'destination' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}