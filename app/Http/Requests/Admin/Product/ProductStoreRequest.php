<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductStoreRequest extends FormRequest
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
     * @return array<string, array>
     */
    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', 'unique:products,slug'],
            'short_description' => ['nullable', 'string'],
            'long_description'  => ['nullable', 'string'],
            'price'             => ['required', 'numeric', 'min:0'],
            'discount_price'    => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'is_active'         => ['boolean'],
            'stock'             => ['required', 'integer', 'min:0'],
            'stock_alert_limit' => ['required', 'integer', 'min:10'],
            'category_ids'      => ['required', 'array', 'min:1'],
            'category_ids.*'    => ['exists:categories,id'],
            'tag_ids'           => ['array'],
            'tag_ids.*'         => ['exists:tags,id'],
            'images'            => ['array'],
            'images.*'          => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'image_ids'         => ['required', 'array'],
            'image_ids.*'       => ['required', 'string'],
            'featured_image'    => ['required', 'string']
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'slug'         => Str::slug($this->slug ?? $this->name),
                         'is_active'    => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                         'category_ids' => json_decode($this->category_ids, true),
                         'tag_ids'      => json_decode($this->tag_ids, true),
                     ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Ürün adı zorunludur.',
            'name.string'   => 'Ürün adı metin formatında olmalıdır.',
            'name.max'      => 'Ürün adı en fazla 255 karakter olabilir.',

            'slug.string'   => 'Slug metin formatında olmalıdır.',
            'slug.required' => 'Slug alanı zorunludur.',
            'slug.max'      => 'Slug en fazla 255 karakter olabilir.',
            'slug.unique'   => 'Bu slug zaten kullanılıyor.',

            'short_description.string' => 'Kısa açıklama metin formatında olmalıdır.',
            'long_description.string'  => 'Uzun açıklama metin formatında olmalıdır.',

            'price.required'         => 'Ürün fiyatı zorunludur.',
            'price.numeric'          => 'Ürün fiyatı sayısal olmalıdır.',
            'price.min'              => 'Ürün fiyatı 0 veya daha büyük olmalıdır.',
            'discount_price.numeric' => 'İndirimli fiyat sayısal olmalıdır.',
            'discount_price.min'     => 'İndirimli fiyat 0 veya daha büyük olmalıdır.',
            'discount_price.lt'      => 'İndirimli fiyat normal fiyattan küçük olmalıdır.',

            'is_active.boolean' => 'Aktiflik durumu doğru formatta olmalıdır.',

            'stock.required' => 'Stok bilgisi zorunludur.',
            'stock.integer'  => 'Stok bilgisi sayısal olmalıdır.',
            'stock.min'      => 'Stok miktarı 0 veya daha büyük olmalıdır.',

            'category_ids.array'    => 'Kategoriler dizi formatında olmalıdır.',
            'category_ids.required' => 'En az bir kategori seçilmelidir.',
            'category_ids.min'      => 'En az bir kategori seçilmelidir.',
            'category_ids.*.exists' => 'Seçili kategori mevcut değil.',

            'tag_ids.array'    => 'Etiketler dizi formatında olmalıdır.',
            'tag_ids.*.exists' => 'Seçili etiket mevcut değil.',

            'images.array'   => 'Resimler dizi formatında olmalıdır.',
            'images.*.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'images.*.mimes' => 'Resim formatı jpeg, png, jpg veya webp olmalıdır.',
            'images.*.max'   => 'Resim boyutu en fazla 2MB olabilir.',
        ];
    }
}
