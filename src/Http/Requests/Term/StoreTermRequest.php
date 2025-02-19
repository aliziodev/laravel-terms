<?php

namespace Aliziodev\LaravelTerms\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:terms,id',
            'order' => 'nullable|integer',
            'meta' => 'nullable|array',
            'meta.*' => 'nullable'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Term name is required',
            'type.required' => 'Term type is required',
            'parent_id.exists' => 'Parent term does not exist',
        ];
    }
}
