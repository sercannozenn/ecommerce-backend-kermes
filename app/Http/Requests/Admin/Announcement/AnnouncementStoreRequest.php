<?php

namespace App\Http\Requests\Admin\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'is_active'    => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                     ]);
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'type'              => ['required', 'in:announcement,event'],
            'date'              => ['required', 'date'],
            'short_description' => ['nullable', 'string'],
            'description'       => ['nullable', 'string'],
            'image'             => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active'         => ['required', 'boolean'],
        ];
    }
}
