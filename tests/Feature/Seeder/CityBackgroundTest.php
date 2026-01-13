<?php

use Database\Seeders\DatabaseSeeder;
use Workbench\App\Models\City;

it('seeds every city with a non-empty background and correct capital flags', function () {
    $this->seed(DatabaseSeeder::class);

    $cities = City::all();
    expect($cities->count())->toBeGreaterThan(0);

    // Every city has a non-empty background (<=200 chars)
    foreach ($cities as $city) {
        expect($city->background)->not->toBeNull();
        expect(trim((string) $city->background))->not->toBe('');
        expect(mb_strlen((string) $city->background))->toBeLessThanOrEqual(200);
    }

    // Known capitals should be flagged
    $capitals = ['Berlin', 'Paris', 'London', 'Bern', 'Amsterdam', 'Brussels'];
    foreach ($capitals as $name) {
        $c = $cities->firstWhere('name', $name);
        expect($c)->not->toBeNull();
        expect((bool) $c->is_capital)->toBeTrue();
    }

    // At least one known non-capital is false
    $nonCapital = $cities->firstWhere('name', 'Munich');
    expect($nonCapital)->not->toBeNull();
    expect((bool) $nonCapital->is_capital)->toBeFalse();
});
