<?php

namespace Tests\Unit\Traits;

use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;

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
