<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'population' => $this->faker->numberBetween(10_000, 5_000_000),
            'background' => $this->faker->sentence(),
            'country_id' => Country::factory(),
        ];
    }
}
