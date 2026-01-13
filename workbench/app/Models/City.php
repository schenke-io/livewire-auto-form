<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function rivers(): BelongsToMany
    {
        return $this->belongsToMany(River::class, 'city_river')
            ->withPivot('bridge_count')
            ->withTimestamps();
    }
}
