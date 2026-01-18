<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGeography();
        $this->seedUsers();
    }

    protected function seedGeography(): void
    {
        $de = Country::factory()->create(['name' => 'Germany', 'code' => 'DE']);
        $fr = Country::factory()->create(['name' => 'France', 'code' => 'FR']);
        $uk = Country::factory()->create(['name' => 'United Kingdom', 'code' => 'UK']);
        $nl = Country::factory()->create(['name' => 'Netherlands', 'code' => 'NL']);
        $be = Country::factory()->create(['name' => 'Belgium', 'code' => 'BE']);
        $ch = Country::factory()->create(['name' => 'Switzerland', 'code' => 'CH']);

        City::factory()->create(['name' => 'Berlin', 'is_capital' => true, 'country_id' => $de->id]);
        City::factory()->create(['name' => 'Munich', 'country_id' => $de->id]);
        City::factory()->create(['name' => 'Paris', 'is_capital' => true, 'country_id' => $fr->id]);
        City::factory()->create(['name' => 'London', 'is_capital' => true, 'country_id' => $uk->id]);
        City::factory()->create(['name' => 'Amsterdam', 'is_capital' => true, 'country_id' => $nl->id]);
        City::factory()->create(['name' => 'Brussels', 'is_capital' => true, 'country_id' => $be->id]);
        City::factory()->create(['name' => 'Bern', 'is_capital' => true, 'country_id' => $ch->id]);

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
