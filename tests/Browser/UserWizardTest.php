<?php

use Workbench\App\Models\User;

it('tests the complete user wizard flow', function () {
    $this->visit('/wizard')
        ->waitForText('User Profile Wizard')
        // Step 1: Account
        ->type("[dusk='name']", 'John Doe')
        ->type("[dusk='email']", 'john@example.com')
        ->press('Next Step')
        // Step 2: Address
        ->waitForText('Home Address')
        ->type("[dusk='address']", '123 Main St')
        ->type("[dusk='zip_code']", '12345')
        ->type("[dusk='city']", 'Sample City')
        ->press('Next Step')
        // Step 3: Contact
        ->waitForText('Contact Details')
        ->type("[dusk='phone']", '555-1234')
        ->press('Next Step')
        // Step 4: Options
        ->waitForText('Preferences')
        ->check("[dusk='marketing_opt_in']")
        ->press('Save Changes')
        ->waitForText('Saved successfully');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St',
        'zip_code' => '12345',
        'city' => 'Sample City',
        'phone' => '555-1234',
        'marketing_opt_in' => 1, // boolean in SQLite
    ]);
});

it('prevents moving forward with invalid data', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->visit('/wizard')
        ->type("[dusk='name']", 'John Doe')
        ->type("[dusk='email']", 'duplicate@example.com')
        ->press('Next Step')
        ->waitForText('taken')
        ->assertDontSee('Home Address');
});

it('tests the back button and data persistence', function () {
    $this->visit('/wizard')
        ->type("[dusk='name']", 'Initial Name')
        ->type("[dusk='email']", 'initial@example.com')
        ->press('Next Step')
        ->waitForText('Home Address')
        ->press('Previous')
        ->waitForText('Account Information')
        ->assertValue("[dusk='name']", 'Initial Name');
});
