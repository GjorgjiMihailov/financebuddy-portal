<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'role'          => ['required', 'in:admin,accountant,company_admin'],
            'company_ids'   => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Внесете ime и презиме.',
            'email.required'    => 'Внесете е-пошта.',
            'email.unique'      => 'Оваа е-пошта веќе постои.',
            'password.required' => 'Внесете лозинка.',
            'password.confirmed'=> 'Лозинките не се совпаѓаат.',
            'role.required'     => 'Изберете улога.',
            'role.in'           => 'Невалидна улога.',
        ];
    }
}
