<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Enums\BrandGroup;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $groups = BrandGroup::cases();
        $group = $groups[array_rand($groups)];

        return [
            'name' => $this->faker->unique()->company(),
            'group' => $group,
            'city_id' => City::factory(),
        ];
    }
}
