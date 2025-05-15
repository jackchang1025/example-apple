<?php

namespace App\Http\Requests;

use App\Rules\EmailOrPhoneValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyAccountRequest extends FormRequest
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
        return [
            'accountName' => ['required', new EmailOrPhoneValidationRule()],
            'password' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'accountName' => '账号',
            'password' => '密码',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'accountName.required' => 'The account name field is required.',
            'accountName.string' => 'The account name must be a string.',
            'accountName.email' => 'When using an email, please provide a valid email address.',
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
        ];
    }
}
