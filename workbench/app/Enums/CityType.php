<?php

namespace Workbench\App\Enums;

use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use Workbench\App\Enums\Traits\ProjectEnumTrait;

enum CityType: string implements AutoFormOptions
{
    use ProjectEnumTrait;

    public const OPTION_TRANSLATION_PREFIX = 'enums.city_type';

    case CAPITAL = 'capital';
    case REGIONAL = 'regional';
    case SMALL = 'small';

    public function icon(): string
    {
        return match ($this) {
            self::CAPITAL => 'heroicon-o-home',
            self::REGIONAL => 'heroicon-o-building-office',
            self::SMALL => 'heroicon-o-map-pin',
        };
    }
}
