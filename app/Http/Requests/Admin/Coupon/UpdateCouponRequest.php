<?php

namespace App\Http\Requests\Admin\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
        $couponId = $this->route('id');

        return [
            'code'           => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId)],
            'type'           => ['required', 'in:fixed,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_uses'       => ['nullable', 'integer', 'min:1'],
            'camp_id'        => ['nullable', 'exists:camps,id'],
            'referee_ids'    => ['nullable', 'array'],
            'referee_ids.*'  => ['exists:users,id'],
            'expires_at'     => ['nullable', 'date'],
            'status'         => ['required', 'in:active,inactive'],
        ];
    }
}
