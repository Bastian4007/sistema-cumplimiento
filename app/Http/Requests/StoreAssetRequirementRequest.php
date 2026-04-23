<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isOperative();
    }

    public function rules(): array
    {
        return [
            'requirement_template_id' => ['required', 'integer', 'exists:requirement_templates,id'],
            'type' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],

            'recurrence_interval' => ['nullable', 'integer', 'min:1'],
            'recurrence_unit' => ['nullable', 'in:day,week,month,year'],
            'recurrence_anchor' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $interval = $this->input('recurrence_interval');
            $unit = $this->input('recurrence_unit');

            if (($interval && ! $unit) || (! $interval && $unit)) {
                $validator->errors()->add(
                    'recurrence_unit',
                    'recurrence_interval y recurrence_unit deben venir juntos.'
                );
            }
        });
    }
}