<?php

namespace Tests\Unit\Traits;

use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;

uses()->group('traits');

beforeEach(function () {
    \Illuminate\Support\Facades\Schema::create('localised_models', function (\Illuminate\Database\Schema\Blueprint $table) {
        $table->string('id')->primary();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

enum ColorEnum: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    case RED = 'red';
    case BLUE = 'blue';
}

enum PrefixedColorEnum: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    public const OPTION_TRANSLATION_PREFIX = 'enums.colors';

    case RED = 'red';
    case BLUE = 'blue';
}

enum IconColorEnum: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    case RED = 'red';
    case BLUE = 'blue';

    public function icon(): string
    {
        return 'heroicon-o-'.$this->value;
    }
}

/**
 * Helper model for testing localised options.
 */
class LocalisedModel extends \Illuminate\Database\Eloquent\Model implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    protected $table = 'localised_models';

    protected $fillable = ['id', 'name'];

    public $incrementing = false;

    public function icon(): string
    {
        return 'heroicon-m-model';
    }
}

it('generates options using default snake class prefix when no mask or constant provided', function () {
    $options = ColorEnum::getOptions();

    expect($options)->toBeArray()
        ->and($options['red'])->toBe('color_enum.red')
        ->and($options['blue'])->toBe('color_enum.blue');
});

it('uses class constant OPTION_TRANSLATION_PREFIX when provided', function () {
    $options = PrefixedColorEnum::getOptions();

    expect($options['red'])->toBe('enums.colors.red')
        ->and($options['blue'])->toBe('enums.colors.blue');
});

it('allows overriding prefix via labelMask parameter', function () {
    $options = ColorEnum::getOptions('ui.labels.colors');

    expect($options['red'])->toBe('ui.labels.colors.red')
        ->and($options['blue'])->toBe('ui.labels.colors.blue');
});

it('includes icons when icon() method exists', function () {
    $options = IconColorEnum::getOptions();

    expect($options['red'])->toBe(['icon_color_enum.red', 'heroicon-o-red'])
        ->and($options['blue'])->toBe(['icon_color_enum.blue', 'heroicon-o-blue']);
});

it('generates options for models with localization and icons', function () {
    LocalisedModel::create(['id' => 'm1', 'name' => 'Model 1']);

    $options = LocalisedModel::getOptions();

    expect($options)->toBeArray()
        ->and($options['m1'])->toBe('localised_model.m1');
});
