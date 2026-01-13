<?php

namespace Workbench\App\Models;

use Database\Factories\Models\RiverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class River extends Model
{
    /** @use HasFactory<RiverFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'length_km',
    ];

    protected static function newFactory(): RiverFactory
    {
        return RiverFactory::new();
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_river')
            ->withPivot('bridge_count')
            ->withTimestamps();
    }
}
