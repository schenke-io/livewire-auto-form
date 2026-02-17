<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;

class JsonCastModelTest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];
}

test('findRelations identifies relations only if isRelation is true', function () {
    $processor = new DataProcessor;
    $rules = [
        'features.vin' => 'required',
    ];
    $model = new JsonCastModelTest;

    // Currently it identifies 'features' as a relation because it sees a dot
    $relations = $processor->findRelations($rules, '');

    expect($relations)->toContain('features');

    // With model it should NOT contain 'features'
    $relations = $processor->findRelations($rules, '', $model);
    expect($relations)->not->toContain('features');
});

test('getAllowedFields does not add id suffix for non-relations', function () {
    $processor = new DataProcessor;
    $rules = [
        'features.vin' => 'required',
    ];
    $model = new JsonCastModelTest;

    $allowed = $processor->getAllowedFields($rules, '', $model);

    expect($allowed)->not->toContain('features_id');
});
