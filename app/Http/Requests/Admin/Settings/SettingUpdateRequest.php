<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key'     => ['required', 'string', 'unique:settings,key,' . $this->setting->id],
            'value'   => ['nullable', 'string'],
            'logo'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'mimes:ico,png,svg', 'max:1024'],
        ];
    }
}
