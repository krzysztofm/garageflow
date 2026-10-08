<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    public function rules(): array
    {
        return [
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'position' => ['sometimes', 'required', 'string', 'max:100'],
        ];
    }
}