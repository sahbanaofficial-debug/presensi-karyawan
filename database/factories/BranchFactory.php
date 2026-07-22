<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
final class BranchFactory extends Factory
{
    /**
     * Model yang digunakan factory.
     *
     * @var class-string<Branch>
     */
    protected $model = Branch::class;

    /**
     * Data bawaan cabang untuk pengujian.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(
                fake()->unique()->bothify('CB-###')
            ),

            'name' => fake()->company(),

            'address' => fake()->address(),

            'latitude' => 3.59519600,

            'longitude' => 98.67222600,

            'geofence_radius' => 30.00,

            'status' => 'active',
        ];
    }
}
