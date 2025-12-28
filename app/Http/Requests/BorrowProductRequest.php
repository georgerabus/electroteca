<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BorrowProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'return_date' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'return_date.required' => 'Please specify when you plan to return the item.',
            'return_date.date' => 'Return date must be a valid date.',
            'return_date.after' => 'Return date must be in the future.',
            'reason.max' => 'Reason must not exceed 500 characters.',
        ];
    }
}
