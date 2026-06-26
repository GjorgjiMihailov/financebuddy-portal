<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Company::class);
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'tax_id'            => ['required', 'string', 'max:20', 'unique:companies,tax_id'],
            'is_vat_registered' => ['boolean'],
            'vat_number'        => ['nullable', 'string', 'max:20', 'required_if:is_vat_registered,true'],
            'address'           => ['nullable', 'string', 'max:500'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Називот е задолжителен.',
            'tax_id.required'      => 'ЕДБ е задолжителен.',
            'tax_id.unique'        => 'Компанија со овој ЕДБ веќе постои.',
            'vat_number.required_if' => 'ДДВ бројот е задолжителен за ДДВ обврзници.',
            'email.email'          => 'Внесете валидна е-пошта.',
        ];
    }
}
