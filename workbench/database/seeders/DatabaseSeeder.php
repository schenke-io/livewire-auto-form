<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;
use Workbench\App\Models\User;

/**
 * The DatabaseSeeder class is responsible for populating the database with
 * initial data for testing and development. It seeds countries, cities,
 * and users, including geographic data for testing nested JSON attributes.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGeography();
        $this->seedUsers();
    }

    protected function seedGeography(): void
    {
        $de = Country::factory()->create(['name' => 'Germany', 'code' => 'DE', 'geo' => ['latitude' => 52.52, 'longitude' => 13.40]]);
        $fr = Country::factory()->create(['name' => 'France', 'code' => 'FR', 'geo' => ['latitude' => 48.85, 'longitude' => 2.35]]);
        $uk = Country::factory()->create(['name' => 'United Kingdom', 'code' => 'UK', 'geo' => ['latitude' => 51.50, 'longitude' => -0.12]]);
        $nl = Country::factory()->create(['name' => 'Netherlands', 'code' => 'NL', 'geo' => ['latitude' => 52.36, 'longitude' => 4.90]]);
        $be = Country::factory()->create(['name' => 'Belgium', 'code' => 'BE', 'geo' => ['latitude' => 50.85, 'longitude' => 4.35]]);
        $ch = Country::factory()->create(['name' => 'Switzerland', 'code' => 'CH', 'geo' => ['latitude' => 46.94, 'longitude' => 7.44]]);

        City::factory()->create([
            'name' => 'Berlin',
            'is_capital' => true,
            'country_id' => $de->id,
            'geo' => ['latitude' => 52.52, 'longitude' => 13.40, 'diameter' => 38, 'height' => 34],
        ]);
        City::factory()->create([
            'name' => 'Munich',
            'is_capital' => false,
            'country_id' => $de->id,
            'geo' => ['latitude' => 48.13, 'longitude' => 11.58, 'diameter' => 25, 'height' => 519],
        ]);
        City::factory()->create([
            'name' => 'Paris',
            'is_capital' => true,
            'country_id' => $fr->id,
            'geo' => ['latitude' => 48.85, 'longitude' => 2.35, 'diameter' => 10, 'height' => 35],
        ]);
        City::factory()->create([
            'name' => 'London',
            'is_capital' => true,
            'country_id' => $uk->id,
            'geo' => ['latitude' => 51.50, 'longitude' => -0.12, 'diameter' => 45, 'height' => 11],
        ]);
        City::factory()->create([
            'name' => 'Amsterdam',
            'is_capital' => true,
            'country_id' => $nl->id,
            'geo' => ['latitude' => 52.36, 'longitude' => 4.90, 'diameter' => 20, 'height' => -2],
        ]);
        City::factory()->create([
            'name' => 'Brussels',
            'is_capital' => true,
            'country_id' => $be->id,
            'geo' => ['latitude' => 50.85, 'longitude' => 4.35, 'diameter' => 15, 'height' => 13],
        ]);
        City::factory()->create([
            'name' => 'Bern',
            'is_capital' => true,
            'country_id' => $ch->id,
            'geo' => ['latitude' => 46.94, 'longitude' => 7.44, 'diameter' => 12, 'height' => 542],
        ]);

        $de->borders()->attach($fr, ['border_length_km' => 451]);
        $fr->borders()->attach($de, ['border_length_km' => 451]);
    }

    protected function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'zip_code' => '10115',
            'city' => 'Berlin',
            'phone' => '+49 30 123456',
            'marketing_opt_in' => true,
        ]);

        User::factory()->count(5)->create();
    }
}
