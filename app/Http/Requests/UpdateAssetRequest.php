<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperative() === true;
    }

    public function rules(): array
    {
        return [
            'asset_type_id' => [
                'required',
                'integer',
                Rule::exists('asset_types', 'id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],

            'responsible_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('company_id', $this->user()->company_id),
            ],
        ];
    }
}
