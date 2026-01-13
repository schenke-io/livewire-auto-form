<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Country;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->country(),
            'code' => strtoupper($this->faker->unique()->lexify('??')),
        ];
    }
}
