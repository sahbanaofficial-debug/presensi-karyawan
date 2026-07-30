<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterBranchTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('hrd')) {
            return true;
        }

        if (! $user->hasRole('admin')) {
            return false;
        }

        $branchId = (int) $user->branch_id;

        if (
            $branchId <= 0
            || ! Branch::query()
                ->whereKey($branchId)
                ->where(
                    'status',
                    'active'
                )
                ->exists()
        ) {
            return false;
        }

        $requestedBranchId =
            $this->input('branch_id');

        if (
            ! is_string($requestedBranchId)
            && ! is_int($requestedBranchId)
        ) {
            return true;
        }

        $normalized = trim(
            (string) $requestedBranchId
        );

        if (
            $normalized === ''
            || preg_match(
                '/^\d+$/',
                $normalized
            ) !== 1
        ) {
            return true;
        }

        return (int) $normalized === $branchId;
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
