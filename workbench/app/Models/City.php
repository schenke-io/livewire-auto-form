<?php

namespace Workbench\App\Models;

use Database\Factories\Models\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'status' => \Workbench\App\Enums\CityStatus::class,
        ];
    }

    protected static function newFactory(): CityFactory
    {
        return CityFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
