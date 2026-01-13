<?php

namespace Workbench\App\Models;

use Database\Factories\Models\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\App\Enums\BrandGroup;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'group',
        'city_id',
    ];

    protected $casts = [
        'group' => BrandGroup::class,
    ];

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
