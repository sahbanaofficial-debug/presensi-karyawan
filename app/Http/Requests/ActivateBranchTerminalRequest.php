<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ActivateBranchTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'public_id' => strtolower(
                trim(
                    (string) $this->input(
                        'public_id',
                        ''
                    )
                )
            ),

            'activation_code' => trim(
                (string) $this->input(
                    'activation_code',
                    ''
                )
            ),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'public_id' => [
                'required',
                'uuid',
            ],

            'activation_code' => [
                'required',
                'string',
                'digits:8',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'public_id.required' => 'Identitas publik terminal wajib diisi.',

            'public_id.uuid' => 'Identitas publik terminal tidak valid.',

            'activation_code.required' => 'Kode aktivasi terminal wajib diisi.',

            'activation_code.digits' => 'Kode aktivasi terminal harus terdiri dari 8 digit.',
        ];
    }
}
