<?php

namespace App\Http\Requests\Commission;

class UpdateCommissionTargetRequest extends StoreCommissionTargetRequest
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$this->filled('commission_period_id') || !$this->filled('user_id') || !$this->filled('category_id')) {
                return;
            }

            $targetId = $this->route('target')?->id;

            $exists = \App\Models\CommissionTarget::query()
                ->where('commission_period_id', $this->integer('commission_period_id'))
                ->where('user_id', $this->integer('user_id'))
                ->where('category_id', $this->integer('category_id'))
                ->where('id', '!=', $targetId)
                ->exists();

            if ($exists) {
                $validator->errors()->add('category_id', 'رکورد مشابه برای این دوره/کاربر/دسته‌بندی وجود دارد.');
            }
        });
    }
}
