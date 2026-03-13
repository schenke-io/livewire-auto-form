<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedModelOptions;

uses()->group('traits');

beforeEach(function () {
    Schema::create('localised_models', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('localised_model_with_traits', function (Blueprint $table) {
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
class LocalisedModel extends Model implements AutoFormOptions
{
    use AutoFormLocalisedModelOptions;

    protected $table = 'localised_models';

    protected $fillable = ['id', 'name'];

    public $incrementing = false;

    public function icon(): string
    {
        return 'heroicon-m-model';
    }
}

class LocalisedModelWithTrait extends Model implements AutoFormOptions
{
    use AutoFormLocalisedModelOptions;

    protected $table = 'localised_model_with_traits';

    protected $fillable = ['id', 'name'];

    public $incrementing = false;
}

it('generates options using default snake class prefix when no mask or constant provided', function () {
    $options = ColorEnum::getOptions();

    expect($options)->toBeArray()
        ->and($options['RED'])->toBe('ColorEnum.red')
        ->and($options['BLUE'])->toBe('ColorEnum.blue');
});

it('uses class constant OPTION_TRANSLATION_PREFIX when provided', function () {
    $options = PrefixedColorEnum::getOptions();

    expect($options['RED'])->toBe('enums.colors.PrefixedColorEnum.red')
        ->and($options['BLUE'])->toBe('enums.colors.PrefixedColorEnum.blue');
});

it('allows overriding prefix via labelMask parameter', function () {
    $options = ColorEnum::getOptions('ui.labels.colors');

    expect($options['RED'])->toBe('ui.labels.colors.ColorEnum.red')
        ->and($options['BLUE'])->toBe('ui.labels.colors.ColorEnum.blue');
});

it('includes icons when icon() method exists', function () {
    $options = IconColorEnum::getOptions();

    expect($options['RED'])->toBe(['IconColorEnum.red', 'heroicon-o-red'])
        ->and($options['BLUE'])->toBe(['IconColorEnum.blue', 'heroicon-o-blue']);
});

it('generates options for models with localization using AutoFormLocalisedModelOptions', function () {
    LocalisedModelWithTrait::create(['id' => 'm1', 'name' => 'Model 1']);

    $options = LocalisedModelWithTrait::getOptions();

    expect($options)->toBeArray()
        ->and($options['m1'])->toBe('LocalisedModelWithTrait.m1');
});

it('generates options for models with localization and icons', function () {
    LocalisedModel::create(['id' => 'm1', 'name' => 'Model 1']);

    $options = LocalisedModel::getOptions();

    expect($options)->toBeArray()
        ->and($options['m1'])->toBe(['LocalisedModel.m1', 'heroicon-m-model']);
});
