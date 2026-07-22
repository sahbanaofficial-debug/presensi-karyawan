<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Model yang digunakan oleh factory.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * Password yang digunakan ulang selama proses test.
     */
    protected static ?string $password = null;

    /**
     * Data bawaan pengguna.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),

            'email' => fake()
                ->unique()
                ->safeEmail(),

            'password' => static::$password ??= Hash::make(
                'password'
            ),

            'role' => 'employee',

            'status' => 'active',

            'remember_token' => Str::random(10),
        ];
    }
}
