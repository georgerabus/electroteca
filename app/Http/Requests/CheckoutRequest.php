<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'shipping_address' => 'required|string|min:5|max:500',
            'period_from' => 'required|date_format:Y-m-d|after_or_equal:today',
            'period_to' => 'required|date_format:Y-m-d|after:period_from',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Cart cannot be empty.',
            'items.min' => 'Please add at least one item to checkout.',
            'items.*.product_id.exists' => 'One or more products do not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'shipping_address.required' => 'Shipping address is required.',
            'shipping_address.min' => 'Shipping address must be at least 5 characters.',
            'period_from.required' => 'Start date is required.',
            'period_from.date_format' => 'Start date must be a valid date.',
            'period_from.after_or_equal' => 'Start date must be today or in the future.',
            'period_to.required' => 'End date is required.',
            'period_to.date_format' => 'End date must be a valid date.',
            'period_to.after' => 'End date must be after the start date.',
        ];
    }
}
