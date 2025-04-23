<?php

namespace App\Http\Requests\Admin\Product;

use App\Enums\DiscountAmountType;
use App\Enums\DiscountTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ProductDiscountRequest extends FormRequest
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
            'name'            => ['required', 'string', 'max:255'],
            'target_type'     => ['required', 'in:' . implode(',', DiscountTargetType::values())],
            'targets'         => ['required', 'array', 'min:1'],
            'targets.*'       => ['required', 'integer'],
            'discount_type'   => ['required', 'in:' . implode(',', DiscountAmountType::values())],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_start'  => ['required', 'date', 'after_or_equal:today'],
            'discount_end'    => ['required', 'date', 'after_or_equal:discount_start'],
            'priority'        => ['nullable', 'integer', 'min:0'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'İndirim adı zorunludur.',

            'target_type.required' => 'Hedef tipi zorunludur.',
            'target_type.in'       => 'Geçersiz hedef tipi seçildi.',

            'targets.required'  => 'En az bir hedef seçilmelidir.',
            'targets.array'     => 'Hedefler geçerli bir dizi olmalıdır.',
            'targets.*.integer' => 'Seçilen hedef geçerli bir ID olmalıdır.',

            'discount_type.required' => 'İndirim tipi zorunludur.',
            'discount_type.in'       => 'Geçersiz indirim tipi seçildi.',

            'discount_amount.required' => 'İndirim miktarı zorunludur.',
            'discount_amount.numeric'  => 'İndirim miktarı sayısal olmalıdır.',
            'discount_amount.min'      => 'İndirim miktarı negatif olamaz.',

            'discount_start.required' => 'Başlangıç tarihi zorunludur.',
            'discount_start.date'     => 'Başlangıç tarihi geçerli bir tarih olmalıdır.',

            'discount_end.required'       => 'Bitiş tarihi zorunludur.',
            'discount_end.date'           => 'Bitiş tarihi geçerli bir tarih olmalıdır.',
            'discount_end.after_or_equal' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',

            'priority.integer' => 'Öncelik değeri tam sayı olmalıdır.',
            'priority.min'     => 'Öncelik değeri negatif olamaz.',

            'is_active.boolean' => 'Aktiflik durumu sadece true ya da false olabilir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
                         'is_active'    => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
                         'category_ids' => json_decode($this->category_ids, true),
                         'tag_ids'      => json_decode($this->tag_ids, true),
                         'brand_ids'    => json_decode($this->brand_ids, true),
                         'targets'      => json_decode($this->targets, true),
                     ]);
    }
}
