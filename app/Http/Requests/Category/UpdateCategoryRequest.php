<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'slug' => [
                'sometimes', 
                'string', 
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories')->where(function ($query) use ($categoryId) {
                    return $query->where('tenant_id', auth()->user()->tenant_id)
                                ->where('id', '!=', $categoryId);
                })
            ],
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Category name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'slug.unique' => 'This slug is already taken',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'parent_id.exists' => 'The selected parent category does not exist',
            'image_url.url' => 'Image URL must be a valid URL',
            'meta_description.max' => 'Meta description cannot exceed 500 characters',
        ];
    }
}
