<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

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

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'country_language')
            ->withTimestamps();
    }
}
