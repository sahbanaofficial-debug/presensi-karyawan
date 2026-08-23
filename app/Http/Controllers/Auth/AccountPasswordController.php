<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class AccountPasswordController extends Controller
{
    /**
     * Menampilkan formulir perubahan kata sandi akun sendiri.
     */
    public function edit(): View
    {
        return view('auth.password');
    }

    /**
     * Memperbarui kata sandi pengguna yang sedang masuk.
     */
    public function update(
        UpdateAccountPasswordRequest $request
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $user->forceFill([
            'password' => Hash::make(
                (string) $request->validated('password')
            ),
            'remember_token' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('account.password.edit')
            ->with(
                'success',
                'Kata sandi akun berhasil diperbarui.'
            );
    }
}
