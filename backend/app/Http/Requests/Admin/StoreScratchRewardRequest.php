<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScratchRewardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * reward_type is restricted to this enum on purpose — 05's compliance
     * note requires rewards to stay non-cash/non-withdrawable, so there is
     * deliberately no "cash" option to allow here.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reward_type' => ['required', 'string', 'in:bonus_credit,cashback,discount_voucher,none'],
            'value' => ['required', 'numeric', 'min:0'],
            'probability_weight' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
