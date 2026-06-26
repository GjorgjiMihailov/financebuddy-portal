<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Document::class);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'type'       => ['required', Rule::in(array_column(DocumentType::cases(), 'value'))],
            'file'       => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,tiff,webp', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Изберете компанија.',
            'company_id.exists'   => 'Избраната компанија не постои.',
            'type.required'       => 'Изберете тип на документ.',
            'type.in'             => 'Невалиден тип на документ.',
            'file.required'       => 'Прикачете документ.',
            'file.mimes'          => 'Дозволени формати: PDF, JPG, PNG, TIFF.',
            'file.max'            => 'Документот е преголем (макс. 20 MB).',
        ];
    }
}
