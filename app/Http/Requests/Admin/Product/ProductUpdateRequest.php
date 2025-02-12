<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * @property mixed $name
 */
class ProductUpdateRequest extends FormRequest
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
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', 'unique:products,slug,' . $this->route('product')],
            'short_description' => ['nullable', 'string'],
            'long_description'  => ['nullable', 'string'],
            'is_active'         => ['boolean'],
            'stock'             => ['sometimes', 'integer', 'min:0'],
            'category_ids'      => ['array'],
            'category_ids.*'    => ['exists:categories,id'],
            'tag_ids'           => ['array'],
            'tag_ids.*'         => ['exists:tags,id'],
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
            'name.string'              => 'Ürün adı metin formatında olmalıdır.',
            'name.max'                 => 'Ürün adı en fazla 255 karakter olabilir.',
            'slug.string'              => 'Slug metin formatında olmalıdır.',
            'slug.required'            => 'Slug alanı zorunludur.',
            'slug.max'                 => 'Slug en fazla 255 karakter olabilir.',
            'slug.unique'              => 'Bu slug zaten kullanılıyor.',
            'short_description.string' => 'Kısa açıklama metin formatında olmalıdır.',
            'long_description.string'  => 'Uzun açıklama metin formatında olmalıdır.',
            'is_active.boolean'        => 'Aktiflik durumu doğru formatta olmalıdır.',
            'stock.integer'            => 'Stok bilgisi sayısal olmalıdır.',
            'stock.min'                => 'Stok miktarı 0 veya daha büyük olmalıdır.',
            'category_ids.array'       => 'Kategoriler dizi formatında olmalıdır.',
            'category_ids.*.exists'    => 'Seçili kategori mevcut değil.',
            'tag_ids.array'            => 'Etiketler dizi formatında olmalıdır.',
            'tag_ids.*.exists'         => 'Seçili etiket mevcut değil.',
        ];
    }

}
