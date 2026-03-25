<?php

namespace SchenkeIo\LivewireAutoForm\Options;

use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

class EnumOptionsAdapter extends BaseOptionsAdapter
{
    /**
     * @param  class-string  $enumClass
     */
    public function __construct(protected string $enumClass) {}

    public function getOptions(?string $labelMask = null, ?string $origin = null): array
    {
        if (is_subclass_of($this->enumClass, AutoFormOptions::class)) {
            return $this->mapOptions($this->enumClass::getOptions($labelMask));
        }

        if ($labelMask && ! str_contains($labelMask, '(name)') && ! str_contains($labelMask, '(value)')) {
            throw LivewireAutoFormException::optionsMaskSyntax($labelMask, $origin ?: static::class);
        }

        /** @var \UnitEnum[] $cases */
        $cases = $this->enumClass::cases();

        return collect($cases)->map(function ($case) use ($labelMask) {
            $value = $case->name;
            $caseValue = $case instanceof \BackedEnum ? $case->value : $case->name;
            $label = $labelMask ? str_replace(['(name)', '(value)'], [(string) $case->name, (string) $caseValue], $labelMask) : Str::headline($case->name);

            return [
                $value,
                __((string) $label),
            ];
        })->toArray();
    }
}
