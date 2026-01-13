<?php

use Database\Seeders\DatabaseSeeder;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('serves all index pages and show pages for ID=1', function () {
    // Migrations are handled by RefreshDatabase; only seed the database here.
    $this->seed(DatabaseSeeder::class);
    // Index pages
    $this->get('/cities')->assertOk();
    $this->get('/countries')->assertOk();
    $this->get('/brands')->assertOk();
    $this->get('/rivers')->assertOk();

    // Show pages (ID=1)
    $this->get('/cities/1')->assertOk()->assertDontSee('ambiguous column name');
    $this->get('/countries/1')->assertOk()->assertDontSee('ambiguous column name');
    $this->get('/brands/1')->assertOk()->assertDontSee('ambiguous column name');
    $this->get('/rivers/1')->assertOk()->assertDontSee('ambiguous column name');
});
