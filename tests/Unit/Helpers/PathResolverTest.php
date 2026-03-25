<?php

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\PathResolver;

class FakeRelationModel extends Model
{
    protected $table = 'irrelevant';

    public function isRelation($key)
    {
        return $key === 'fake';
    }

    public function fake()
    {
        return 'not-a-relation-object';
    }
}

it('treats remaining parts as attributes when relation method does not return Relation', function () {
    $model = new FakeRelationModel;
    $resolver = new PathResolver;

    $info = $resolver->resolve($model, 'fake.attr.more');

    expect($info->relationChain)->toBe(['fake'])
        ->and($info->targetAttribute)->toBe('attr.more');
});
