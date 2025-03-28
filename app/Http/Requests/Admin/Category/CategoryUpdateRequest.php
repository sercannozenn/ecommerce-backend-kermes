<?php

namespace App\Http\Requests\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CategoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'slug'               => ['required', 'string', 'unique:categories,slug,' . $this->category->id],
            'description'        => ['nullable', 'string'],
            'is_active'          => ['required', 'boolean'],
            'tags'               => ['nullable', 'array'],
            'tags.*.value'       => ['nullable', 'exists:tags,id'],
            'keywords'           => ['nullable', 'string'],
            'seo_description'    => ['nullable', 'string'],
            'parent_category_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'slug' => Str::slug($this->slug),
                         'parent_category_id' => $this->filled('parent_category_id') ? $this->parent_category_id : null,
                     ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Kategori adı zorunludur.',
            'name.string'   => 'Kategori adı geçerli bir metin olmalıdır.',
            'name.max'      => 'Kategori adı en fazla :max karakter olabilir.',

            'slug.required' => 'Slug alanı zorunludur.',
            'slug.string'   => 'Slug geçerli bir metin olmalıdır.',
            'slug.unique'   => 'Bu slug zaten kullanılıyor.',

            'description.string' => 'Açıklama metin türünde olmalıdır.',

            'is_active.boolean' => 'Aktiflik bilgisi yalnızca 1 veya 0 olabilir.',

            'tags.array'          => 'Etiketler bir dizi olmalıdır.',
            'tags.*.value.exists' => 'Seçilen etiketlerden biri geçerli değil.',

            'keywords.string' => 'Anahtar kelimeler metin türünde olmalıdır.',

            'seo_description.string' => 'SEO açıklaması metin türünde olmalıdır.',

            'author.string' => 'Yazar bilgisi metin türünde olmalıdır.',

            'parent_category_id.exists' => 'Seçilen üst kategori geçerli değil.',
        ];
    }

}
