<?php

namespace App\Http\Requests\Admin\Slider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class SliderStoreRequest extends FormRequest
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
            'path'          => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'row_1_text'    => ['nullable', 'string', 'max:255'],
            'row_1_color'   => ['nullable', 'string', 'max:255'],
            'row_1_css'     => ['nullable', 'string', 'max:255'],
            'row_2_text'    => ['nullable', 'string', 'max:255'],
            'row_2_color'   => ['nullable', 'string', 'max:255'],
            'row_2_css'     => ['nullable', 'string', 'max:255'],
            'button_text'   => ['nullable', 'string', 'max:255'],
            'button_url'    => ['nullable', 'url', 'max:255'],
            'button_target' => ['nullable', 'string', 'in:_blank,_self'],
            'button_color'  => ['nullable', 'string', 'max:255'],
            'button_css'    => ['nullable', 'string', 'max:255'],
            'is_active'     => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         //                         'is_active'    => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                         'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
                     ]);
    }


    public function messages(): array
    {
        return [
            'path.required' => 'Slider görseli zorunludur.',
            'path.image'    => 'Slider görseli geçerli bir resim olmalıdır.',
            'path.mimes'    => 'Slider görseli jpg, jpeg, png veya webp formatında olmalıdır.',
            'path.max'      => 'Slider görseli en fazla 2MB olmalıdır.',

            'row_1_text.max'  => '1. satır metni en fazla 255 karakter olmalıdır.',
            'row_1_color.max' => '1. satır renk kodu en fazla 255 karakter olmalıdır.',
            'row_1_css.max'   => '1. satır CSS en fazla 255 karakter olmalıdır.',

            'row_2_text.max'  => '2. satır metni en fazla 255 karakter olmalıdır.',
            'row_2_color.max' => '2. satır renk kodu en fazla 255 karakter olmalıdır.',
            'row_2_css.max'   => '2. satır CSS en fazla 255 karakter olmalıdır.',

            'button_text.max'  => 'Buton metni en fazla 255 karakter olmalıdır.',
            'button_url.url'   => 'Buton bağlantısı geçerli bir URL olmalıdır.',
            'button_url.max'   => 'Buton bağlantısı en fazla 255 karakter olmalıdır.',
            'button_target.in' => 'Buton hedefi yalnızca _blank veya _self olabilir.',
            'button_color.max' => 'Buton rengi en fazla 255 karakter olmalıdır.',
            'button_css.max'   => 'Buton CSS en fazla 255 karakter olmalıdır.',

            'is_active.required' => 'Aktiflik durumu zorunludur.',
            'is_active.boolean'  => 'Aktiflik değeri yalnızca true veya false olabilir.',
        ];
    }
}
