<?php

namespace App\Http\Requests\Commission;

use Illuminate\Validation\Rule;

class UpdateCommissionTargetRequest extends StoreCommissionTargetRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $target = $this->route('target');

        $rules['category_id'] = [
            'required',
            'exists:categories,id',
            Rule::unique('commission_targets')
                ->ignore($target?->id)
                ->where(fn ($query) => $query
                    ->where('commission_period_id', $this->integer('commission_period_id'))
                    ->where('user_id', $this->integer('user_id'))
                ),
        ];

        return $rules;
    }
}
