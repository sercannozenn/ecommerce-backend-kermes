<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SettingStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key'     => ['required', 'string', 'unique:settings,key'],
            'value'   => ['nullable', 'string'],
            'logo'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'mimes:ico,png,svg,jpg', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required'   => 'Ayar anahtarı zorunludur.',
            'key.string'     => 'Ayar anahtarı metin olmalıdır.',
            'key.unique'     => 'Bu anahtar zaten kullanılmış.',

            'value.string'   => 'Değer alanı metin olmalıdır.',

            'logo.image'     => 'Logo bir resim dosyası olmalıdır.',
            'logo.mimes'     => 'Logo sadece png, jpg, jpeg veya webp formatında olmalıdır.',
            'logo.max'       => 'Logo en fazla 2MB (2048 KB) boyutunda olmalıdır.',

            'favicon.mimes'  => 'Favicon sadece ico, png veya svg formatında olmalıdır.',
            'favicon.max'    => 'Favicon en fazla 1MB (1024 KB) boyutunda olmalıdır.',
        ];
    }

}
