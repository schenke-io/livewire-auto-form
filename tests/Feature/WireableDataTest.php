<?php

namespace Tests\Feature;

use Livewire\Wireable;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use Workbench\App\Models\City;

class MockWireable implements Wireable
{
    public function __construct(protected string $value) {}

    public function toLivewire()
    {
        return $this->value;
    }

    public static function fromLivewire($value)
    {
        return new static($value);
    }
}

class MockArrayWireable implements Wireable
{
    public function __construct(protected array $value) {}

    public function toLivewire()
    {
        return $this->value;
    }

    public static function fromLivewire($value)
    {
        return new static($value);
    }
}

it('flattens Wireable objects in extractFilteredData', function () {
    $processor = new DataProcessor;
    $model = new City(['name' => 'Berlin']);

    // Use an uncasted field to avoid Eloquent cast validation
    $model->background = new MockWireable('blue');

    $rules = ['name' => 'required', 'background' => 'required'];
    $data = $processor->extractFilteredData($model, $rules, '');

    expect($data['background'])->toBe('blue');
});

it('does not flatten Wireable objects that return arrays in extractFilteredData', function () {
    $processor = new DataProcessor;
    $model = new City(['name' => 'Berlin']);

    $wireable = new MockArrayWireable(['foo' => 'bar']);
    $model->background = $wireable;

    $rules = ['name' => 'required', 'background' => 'required'];
    $data = $processor->extractFilteredData($model, $rules, '');

    // Should NOT be flattened because it's an array
    expect($data['background'])->toBe($wireable);
});

it('flattens Wireable objects in sanitizeValue', function () {
    $processor = new DataProcessor;
    $wireable = new MockWireable('active');

    $sanitized = $processor->sanitizeValue('status', $wireable, []);

    expect($sanitized)->toBe('active');
});

it('does not flatten Wireable objects that return arrays in sanitizeValue', function () {
    $processor = new DataProcessor;
    $wireable = new MockArrayWireable(['foo' => 'bar']);

    $sanitized = $processor->sanitizeValue('status', $wireable, []);

    expect($sanitized)->toBe($wireable);
});
