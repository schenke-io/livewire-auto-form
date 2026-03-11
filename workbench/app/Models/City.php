<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\App\Enums\CityStatus;

/**
 * The City model represents a city within a country, including its geographic
 * data stored in a JSON column. This model defines relationships with countries,
 * validation rules for its attributes, and factory methods for testing.
 *
 * @property string $name
 * @property string|null $background
 * @property int $population
 * @property bool $is_capital
 * @property int $country_id
 * @property string $status
 * @property array<string, mixed> $geo
 */
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'background',
        'population',
        'is_capital',
        'country_id',
        'status',
        'geo',
    ];

    protected function casts(): array
    {
        return [
            'status' => CityStatus::class,
            'geo' => 'array',
        ];
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'background' => 'nullable|string',
            'population' => 'required|integer',
            'is_capital' => 'required|boolean',
            'country_id' => 'required|exists:countries,id',
            'status' => 'required',
            'geo.latitude' => 'required|numeric',
            'geo.longitude' => 'required|numeric',
            'geo.diameter' => 'nullable|integer',
            'geo.height' => 'nullable|integer',
        ];
    }

    /**
     * Get the country that the city belongs to.
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
