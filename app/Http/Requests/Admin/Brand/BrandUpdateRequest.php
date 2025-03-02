<?php

namespace App\Http\Requests\Admin\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BrandUpdateRequest extends FormRequest
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
            'name'            => ['required', 'string', 'max:255'],
            'slug'            => ['required', 'string', 'unique:brands,slug,' . $this->route('brand')],
            'is_active'       => ['boolean'],
            'keywords'        => ['nullable','sometimes', 'string'],
            'seo_description' => ['nullable','sometimes', 'string'],
            'author'          => ['nullable','sometimes', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'slug' => Str::slug($this->slug ?? $this->name),
                     ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Marka Adı alanı zorunludur.',
            'name.string'   => 'Marka Adı alanı metin olmalıdır.',
            'name.max'      => 'Marka Adı alanı en fazla 255 karakter olabilir.',

            'slug.required' => 'Slug alanı zorunludur.',
            'slug.string'   => 'Slug alanı metin olmalıdır.',
            'slug.unique'   => 'Bu slug zaten alınmış.',

            'is_active.boolean' => 'Aktiflik durumu doğru veya yanlış olmalıdır.',

            'keywords.sometimes' => 'Anahtar kelimeler alanı bazen gereklidir.',
            'keywords.string'    => 'Anahtar kelimeler alanı metin olmalıdır.',

            'seo_description.sometimes' => 'SEO açıklaması alanı bazen gereklidir.',
            'seo_description.string'    => 'SEO açıklaması alanı metin olmalıdır.',

            'author.sometimes' => 'Yazar alanı bazen gereklidir.',
            'author.string'    => 'Yazar alanı metin olmalıdır.',
        ];
    }

}
