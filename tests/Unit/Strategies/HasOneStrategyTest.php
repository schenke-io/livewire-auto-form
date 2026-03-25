<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\HasOneStrategy;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\StrategyFactory;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

class HasOneParent extends Model
{
    protected $table = 'has_one_parents';

    public $timestamps = false;

    protected $fillable = ['name'];

    public function child()
    {
        return $this->hasOne(HasOneChild::class, 'parent_id');
    }
}

class HasOneChild extends Model
{
    protected $table = 'has_one_children';

    public $timestamps = false;

    protected $fillable = ['parent_id', 'value'];

    public function parent()
    {
        return $this->belongsTo(HasOneParent::class, 'parent_id');
    }
}

beforeEach(function () {
    Schema::create('has_one_parents', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
    });
    Schema::create('has_one_children', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->string('value')->nullable();
    });
});

it('StrategyFactory returns HasOneStrategy for hasOne relation', function () {
    $parent = HasOneParent::create(['name' => 'P']);
    $strategy = StrategyFactory::make($parent->child());
    expect($strategy)->toBeInstanceOf(HasOneStrategy::class);
});

it('creates a related model when id is null', function () {
    $parent = HasOneParent::create(['name' => 'P']);
    $relation = $parent->child();

    $strategy = new HasOneStrategy;
    $strategy->save($relation, $parent, 'ctx', null, ['value' => 'A'], new FormCollection);

    $child = $parent->child()->first();
    expect($child)->not()->toBeNull()
        ->and($child->value)->toBe('A')
        ->and($child->parent_id)->toBe($parent->id);
});

it('updates a related model when id is provided', function () {
    $parent = HasOneParent::create(['name' => 'P']);
    $child = $parent->child()->create(['value' => 'A']);

    $strategy = new HasOneStrategy;
    $strategy->save($parent->child(), $parent, 'ctx', $child->id, ['value' => 'B'], new FormCollection);

    $child->refresh();
    expect($child->value)->toBe('B');
});

it('deletes a related model by id', function () {
    $parent = HasOneParent::create(['name' => 'P']);
    $child = $parent->child()->create(['value' => 'A']);

    $strategy = new HasOneStrategy;
    $strategy->delete($parent->child(), $parent, 'ctx', $child->id);

    expect(HasOneChild::find($child->id))->toBeNull();
});

it('HasOneStrategy::updateField returns false', function () {
    $strategy = new HasOneStrategy;
    $parent = new HasOneParent(['name' => 'parent']);
    $processor = new DataProcessor;
    $state = new FormCollection;
    expect($strategy->updateField($parent->child(), $parent, 'child', 1, 'name', 'new name', $state, $processor, []))
        ->toBeFalse();
});
