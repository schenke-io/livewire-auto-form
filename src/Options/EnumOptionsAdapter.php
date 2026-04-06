<?php

namespace SchenkeIo\LivewireAutoForm\Options;

use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

/**
 * Class EnumOptionsAdapter
 *
 * Resolves options from a PHP Enum class. Supports custom label formatting.
 */
class EnumOptionsAdapter extends BaseOptionsAdapter
{
    /**
     * @param  class-string  $enumClass  The FQCN of the Enum.
     */
    public function __construct(protected string $enumClass) {}

    /**
     * Resolve options from the Enum cases.
     *
     * @param  string|null  $labelMask  Format for labels (e.g., "(name) - (value)").
     * @param  string|null  $origin  Origin context for error reporting.
     * @return array<int, array{0: string|int, 1: string}>
     *
     * @throws LivewireAutoFormException If the mask syntax is invalid.
     */
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
