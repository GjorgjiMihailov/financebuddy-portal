<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\JournalEntry::class);
    }

    public function rules(): array
    {
        return [
            'group_code'          => ['nullable', 'integer', 'exists:journal_groups,code'],
            'sequence_number'     => ['nullable', 'integer', 'min:1'],
            'description'        => ['required', 'string', 'max:500'],
            'entry_date'         => ['required', 'date'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'post_immediately'   => ['boolean'],
            'lines'              => ['required', 'array', 'min:2'],
            'lines.*.account_code' => ['required', 'string', 'exists:chart_of_accounts,code'],
            'lines.*.debit'      => ['required', 'numeric', 'min:0'],
            'lines.*.credit'     => ['required', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $lines = $this->lines ?? [];
                $totalDebit  = array_sum(array_column($lines, 'debit'));
                $totalCredit = array_sum(array_column($lines, 'credit'));

                if (abs($totalDebit - $totalCredit) > 0.01) {
                    $validator->errors()->add('lines', 'Вкупниот дебит мора да биде еднаков на вкупниот кредит.');
                }

                if ($totalDebit < 0.01) {
                    $validator->errors()->add('lines', 'Внесете барем еден дебит и еден кредит износ.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'description.required'           => 'Внесете опис на книжењето.',
            'entry_date.required'            => 'Внесете датум.',
            'lines.required'                 => 'Додајте ставки.',
            'lines.min'                      => 'Книжењето мора да има барем 2 ставки.',
            'lines.*.account_code.required'  => 'Изберете сметка.',
            'lines.*.account_code.exists'    => 'Избраната сметка не постои.',
            'lines.*.debit.required'         => 'Внесете дебит.',
            'lines.*.credit.required'        => 'Внесете кредит.',
        ];
    }
}
