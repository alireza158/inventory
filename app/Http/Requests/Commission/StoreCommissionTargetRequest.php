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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$this->filled('commission_period_id') || !$this->filled('user_id') || !$this->filled('category_id')) {
                return;
            }

            $exists = \App\Models\CommissionTarget::query()
                ->where('commission_period_id', $this->integer('commission_period_id'))
                ->where('user_id', $this->integer('user_id'))
                ->where('category_id', $this->integer('category_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('category_id', 'برای این کاربر و دسته‌بندی در دوره انتخابی قبلاً تارگت ثبت شده است.');
            }
        });
    }
}
