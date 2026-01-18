<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;

class Country extends Model implements AutoFormOptions
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    public static function getOptions(?string $labelMask = null): array
    {
        if ($labelMask && str_contains($labelMask, '(')) {
            preg_match_all("/\((.*?)\)/", $labelMask, $matches);
            if (empty($matches[1])) {
                throw \SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException::optionsMaskSyntax($labelMask, self::class);
            }
            $columns = array_unique(array_merge(['id'], $matches[1]));

            return self::query()->orderBy('name')->get($columns)->mapWithKeys(function ($m) use ($labelMask, $matches) {
                $label = $labelMask;
                foreach ($matches[1] as $col) {
                    $label = str_replace("($col)", (string) $m->{$col}, $label);
                }

                return [$m->id => $label];
            })->toArray();
        }

        return self::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected $fillable = [
        'name',
        'code',
    ];

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function borders(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_borders', 'country_id', 'neighbor_id')
            ->withTimestamps()
            ->withPivot('border_length_km');
    }
}
