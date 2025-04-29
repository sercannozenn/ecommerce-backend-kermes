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
            'name'                => ['required', 'string', 'max:255'],
            'slug'                => ['required', 'string', 'max:255', 'unique:products,slug'],
            'short_description'   => ['nullable', 'string'],
            'long_description'    => ['nullable', 'string'],
            'price'               => ['required', 'numeric', 'min:0'],
            'price_discount'      => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'is_active'           => ['boolean'],
            'category_ids'        => ['required', 'array', 'min:1'],
            'category_ids.*'      => ['exists:categories,id'],
            'tag_ids'             => ['array'],
            'tag_ids.*'           => ['exists:tags,id'],
            'images'              => ['array'],
            'images.*'            => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'image_ids'           => ['required', 'array'],
            'image_ids.*'         => ['required', 'string'],
            'featured_image'      => ['required', 'string'],
            'brand_id'            => ['nullable', 'exists:brands,id'],
            'sizes'               => 'sometimes|array',
            'sizes.*.size'        => 'required_with:sizes|string|max:50',// 'required_with:sizes' -> bu alanlar yalnızca 'sizes' gönderilmişse zorunlu
            'sizes.*.stock'       => 'required_with:sizes|integer|min:0',
            'sizes.*.stock_alert' => 'required_with:sizes|integer|min:0',
            'keywords'            => ['nullable', 'sometimes', 'string'],
            'seo_description'     => ['nullable', 'sometimes', 'string'],
            'author'              => ['nullable', 'sometimes', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'slug'         => Str::slug($this->slug ?? $this->name),
                         'is_active'    => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                         'category_ids' => json_decode($this->category_ids, true),
                         'tag_ids'      => json_decode($this->tag_ids, true),
                         'sizes'        => json_decode($this->sizes, true),
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
            'price_discount.numeric' => 'İndirimli fiyat sayısal olmalıdır.',
            'price_discount.min'     => 'İndirimli fiyat 0 veya daha büyük olmalıdır.',
            'price_discount.lt'      => 'İndirimli fiyat normal fiyattan küçük olmalıdır.',

            'is_active.boolean' => 'Aktiflik durumu doğru formatta olmalıdır.',

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

            'sizes.array'                       => 'Beden bilgileri dizi formatında olmalı.',
            'sizes.*.size.required_with'        => 'Her bedenin bir adı (size) olmalıdır.',
            'sizes.*.size.string'               => 'Beden adı metin (string) olmalı.',
            'sizes.*.stock.required_with'       => 'Her beden için stok girilmelidir.',
            'sizes.*.stock.integer'             => 'Stok sayısı tam sayı olmalı.',
            'sizes.*.stock.min'                 => 'Stok en az 0 olabilir.',
            'sizes.*.stock_alert.required_with' => 'Her beden için stok uyarı eşiği girilmeli.',
            'sizes.*.stock_alert.integer'       => 'Stok uyarı eşiği tam sayı olmalı.',
            'sizes.*.stock_alert.min'           => 'Stok uyarı eşiği en az 0 olabilir.',

            'keywords.sometimes' => 'Anahtar kelimeler alanı bazen gereklidir.',
            'keywords.string'    => 'Anahtar kelimeler alanı metin olmalıdır.',

            'seo_description.sometimes' => 'SEO açıklaması alanı bazen gereklidir.',
            'seo_description.string'    => 'SEO açıklaması alanı metin olmalıdır.',

            'author.sometimes' => 'Yazar alanı bazen gereklidir.',
            'author.string'    => 'Yazar alanı metin olmalıdır.',
        ];
    }
}
