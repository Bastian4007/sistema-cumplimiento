<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperative() === true;
    }

    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'asset_type_id' => [
                'required',
                'integer',
                Rule::exists('asset_types', 'id'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'code')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'responsible_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'compliance_start_date' => ['required', 'date'],
            'compliance_due_date'   => ['required', 'date', 'after_or_equal:compliance_start_date'],
        ];
    }
}