<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We'll handle authorization in the controller and middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
        ];

        // If this is an update (PUT/PATCH request), make all fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules = [
                'title' => 'sometimes|string|max:255',
                'amount' => 'sometimes|numeric|min:0',
                'category' => 'sometimes|string|max:100',
            ];
        }

        return $rules;
    }
}