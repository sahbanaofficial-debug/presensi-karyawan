<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterBranchTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $this->input(
                        'name',
                        ''
                    )
                ) ?? ''
            ),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'Cabang terminal wajib dipilih.',

            'branch_id.exists' => 'Cabang terminal tidak ditemukan.',

            'name.required' => 'Nama terminal wajib diisi.',

            'name.min' => 'Nama terminal minimal 3 karakter.',

            'name.max' => 'Nama terminal maksimal 100 karakter.',
        ];
    }
}
