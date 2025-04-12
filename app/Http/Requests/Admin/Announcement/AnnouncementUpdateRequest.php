<?php

namespace App\Http\Requests\Admin\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'is_active' => $this->has('is_active') ? (bool) $this->input('is_active') : false,
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
