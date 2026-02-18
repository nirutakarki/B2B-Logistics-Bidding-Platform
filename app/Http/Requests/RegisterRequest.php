<?php

namespace App\Http\Requests;

use App\Shared\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'in:' . implode(',', array_column(BusinessType::cases(), 'value'))],
            'address' => ['nullable', 'string', 'max:500'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'business_name' => 'business name',
            'business_type' => 'business type',
        ];
    }
}
