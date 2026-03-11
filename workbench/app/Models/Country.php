<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

/**
 * The Country model represents a nation that can contain multiple cities.
 * It manages attributes such as name, ISO code, and geographic boundaries.
 * Relationships with cities and neighboring countries are defined here,
 * along with validation rules and helper methods for Livewire components.
 *
 * @property string $name
 * @property string $code
 * @property array<string, mixed> $geo
 * @property mixed $pivot
 */
class Country extends Model implements AutoFormOptions
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    public static function getOptions(?string $labelMask = null): array
    {
        if ($labelMask && str_contains($labelMask, '(')) {
            preg_match_all("/\((.*?)\)/", $labelMask, $matches);
            if (empty($matches[1])) {
                throw LivewireAutoFormException::optionsMaskSyntax($labelMask, self::class);
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
        'geo',
    ];

    protected function casts(): array
    {
        return [
            'geo' => 'array',
        ];
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer',
            'name' => 'required|string',
            'code' => 'required|string|size:2',
            'geo.latitude' => 'nullable|numeric',
            'geo.longitude' => 'nullable|numeric',
            'pivot.border_length_km' => 'nullable|integer',
        ];
    }

    /**
     * Get the cities that belong to the country.
     *
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get the neighboring countries for this country.
     *
     * @return BelongsToMany<Country, $this>
     */
    public function borders(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_borders', 'country_id', 'neighbor_id')
            ->withTimestamps()
            ->withPivot('border_length_km');
    }
}
