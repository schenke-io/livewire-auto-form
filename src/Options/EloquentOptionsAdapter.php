<?php

namespace SchenkeIo\LivewireAutoForm\Options;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

class EloquentOptionsAdapter extends BaseOptionsAdapter
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(protected string $modelClass) {}

    public function getOptions(?string $labelMask = null, ?string $origin = null): array
    {
        if (is_subclass_of($this->modelClass, AutoFormOptions::class)) {
            return $this->mapOptions($this->modelClass::getOptions($labelMask));
        }

        /** @var Model $instance */
        $instance = new $this->modelClass;
        $idColumn = $instance->getKeyName();

        if ($labelMask && str_contains($labelMask, '(')) {
            // It's a mask
            preg_match_all("/\((.*?)\)/", $labelMask, $matches);
            if (empty($matches[1])) {
                throw LivewireAutoFormException::optionsMaskSyntax($labelMask, $origin ?: static::class);
            }
            $columns = array_unique(array_merge([$idColumn], $matches[1]));

            return $this->modelClass::all($columns)->map(function ($m) use ($idColumn, $labelMask, $matches) {
                $label = $labelMask;
                foreach ($matches[1] as $col) {
                    $label = str_replace("($col)", (string) $m->{$col}, $label);
                }

                return [
                    $m->{$idColumn},
                    __($label),
                ];
            })->toArray();
        } else {
            // It's a column name (or null)
            $labelColumn = $labelMask ?: 'name';

            return $this->modelClass::all([$idColumn, $labelColumn])->map(fn ($m) => [
                $m->{$idColumn},
                __($m->{$labelColumn}),
            ])->toArray();
        }
    }
}
