<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommissionTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'commission_period_id' => ['required', 'exists:commission_periods,id'],
            'user_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'target_amount' => ['required', 'integer', 'min:0'],
            'target_qty' => ['nullable', 'integer', 'min:0'],
            'commission_type' => ['required', Rule::in(['percent', 'fixed'])],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'min_percent_to_activate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
