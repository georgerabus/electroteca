<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'slug' => 'required|string|min:3|max:255|unique:products,slug,' . ($this->product?->id ?? 'NULL'),
            'description' => 'required|string|min:10|max:2000',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|in:USD,EUR,MDL',
            'stock_quantity' => 'required|integer|min:0|max:10000',
            'is_available' => 'boolean',
            'image_url' => 'nullable|url|max:2000',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.min' => 'Product name must be at least 3 characters.',
            'slug.unique' => 'This product slug already exists.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'stock_quantity.integer' => 'Stock quantity must be a whole number.',
            'category_id.exists' => 'Selected category does not exist.',
        ];
    }
}
