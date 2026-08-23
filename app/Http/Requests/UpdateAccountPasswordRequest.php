<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdateAccountPasswordRequest extends FormRequest
{
    /**
     * Seluruh pengguna aktif boleh mengganti kata sandinya sendiri.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true;
    }

    /**
     * Aturan validasi perubahan kata sandi akun.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'bail',
                'required',
                'string',
                'current_password:web',
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'different:current_password',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',

            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.min' => 'Kata sandi baru minimal 12 karakter.',
            'password.mixed' => 'Kata sandi baru harus menggunakan huruf besar dan huruf kecil.',
            'password.numbers' => 'Kata sandi baru harus mengandung angka.',
            'password.symbols' => 'Kata sandi baru harus mengandung simbol.',
        ];
    }
}
