<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\River;

/**
 * @extends Factory<River>
 */
class RiverFactory extends Factory
{
    protected $model = River::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().' River',
            'length_km' => $this->faker->numberBetween(50, 5000),
        ];
    }
}
