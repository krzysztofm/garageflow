<?php

namespace App\Http\Requests\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('businessTrip')
        );
    }

    public function rules(): array
    {
        return [
            'start_date' => [
                'required_with:end_date',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'required_with:start_date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'destination' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['planned', 'completed', 'cancelled']),
            ],
        ];
    }
}