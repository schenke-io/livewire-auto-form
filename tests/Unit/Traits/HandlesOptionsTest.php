<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Tests\Feature\Livewire\Components\Models\ModelWithPureEnum;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

class ModelWithOptions extends \Illuminate\Database\Eloquent\Model implements AutoFormOptions
{
    protected $table = 'model_with_options';

    public static function getOptions(?string $labelMask = null): array
    {
        return [1 => 'Option 1', 2 => 'Option 2'];
    }
}

enum EnumWithOptions: string implements AutoFormOptions
{
    case One = 'one';

    public static function getOptions(?string $labelMask = null): array
    {
        return ['one' => 'Custom One'];
    }
}

class ModelWithEnumRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_with_enum_relation';

    protected $casts = ['status' => EnumWithOptions::class];
}

class ModelWithRelationWithOptions extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_with_rel_options';

    public function optionsRelation()
    {
        return $this->hasMany(ModelWithOptions::class, 'id', 'id');
    }
}

class ModelRelated extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_related';

    protected $fillable = ['name', 'code'];
}

class ModelWithVariousRelations extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_with_various_relations';

    protected $fillable = ['model_related_id', 'parent_id'];

    public function belongsToRelation()
    {
        return $this->belongsTo(ModelRelated::class, 'model_related_id');
    }

    public function hasManyRelation()
    {
        return $this->hasMany(ModelRelated::class, 'parent_id');
    }
}

beforeEach(function () {
    Schema::create('model_with_pure_enums', function (Blueprint $table) {
        $table->id();
        $table->string('pure')->nullable();
        $table->timestamps();
    });
    Schema::create('model_with_options', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
    Schema::create('model_with_enum_relation', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
        $table->timestamps();
    });
    Schema::create('model_with_rel_options', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
    Schema::create('model_related', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('code')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->timestamps();
    });
    Schema::create('model_with_various_relations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('model_related_id')->nullable();
        $table->timestamps();
    });
});

it('resolves model options using AutoFormOptions via relation', function () {
    $model = new ModelWithRelationWithOptions;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['optionsRelation.name' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('optionsRelation');

    expect($options)->toBeArray()
        ->and($options)->toContain([1, 'Option 1'])
        ->and($options)->toContain([2, 'Option 2']);
});

it('resolves enum options using AutoFormOptions', function () {
    $model = new ModelWithEnumRelation;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status');

    expect($options)->toBeArray()
        ->and($options)->toContain(['one', 'Custom One']);
});

it('resolves model options for a relation', function () {
    $country = Country::factory()->create(['name' => 'Test Country']);
    $city = City::factory()->create(['country_id' => $country->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.name' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('country');

    expect($options)->toBeArray()
        ->and($options)->toContain([$country->id, 'Test Country']);
});

it('resolves enum options from related model using dot notation', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    // We can't easily add a cast to Country model dynamically,
    // but we can test the dot notation logic in optionsFor which calls enumOptionsFor

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.status' => 'required'],
    ]);

    // This will try to resolve Enum for 'status' on 'country' relation
    // Since Country doesn't have 'status' cast, it should throw or return empty
    // Actually, enumOptionsFor throws missingEnumCast

    expect(fn () => $component->instance()->optionsFor('country.status'))
        ->toThrow(LivewireAutoFormException::class, '[Tests\Feature\Livewire\Components\FlexibleTestComponent]');
});

it('resolves model options with a label mask', function () {
    $country = Country::factory()->create(['name' => 'Test Country', 'code' => 'TC']);
    $city = City::factory()->create(['country_id' => $country->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.name' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('country', '(name) - (code)');

    expect($options)->toBeArray()
        ->and($options)->toContain([$country->id, 'Test Country - TC']);
});

it('resolves enum options', function () {
    $model = new ModelWithPureEnum;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['pure' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('pure');

    expect($options)->toBeArray()
        ->and($options)->toContain(['Alpha', 'Alpha'])
        ->and($options)->toContain(['Beta', 'Beta']);
});

it('resolves enum options with label mask', function () {
    $model = new ModelWithPureEnum;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['pure' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('pure', 'Case: (name)');

    expect($options)->toBeArray()
        ->and($options)->toContain(['Alpha', 'Case: Alpha']);
});

it('throws exception for non-existent relation', function () {
    $city = City::factory()->create();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['non_existent' => 'required'],
    ]);

    expect(fn () => $component->instance()->optionsFor('non_existent'))
        ->toThrow(LivewireAutoFormException::class, '[Tests\Feature\Livewire\Components\FlexibleTestComponent]');
});

it('throws exception for invalid relation type', function () {
    $city = City::factory()->create();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
    ]);

    // 'name' is an attribute, not a relation, but if we call modelOptionsFor it should fail
    expect(fn () => $component->instance()->modelOptionsFor('name'))
        ->toThrow(LivewireAutoFormException::class, '[Tests\Feature\Livewire\Components\FlexibleTestComponent]');
});

it('throws exception if root model is not set', function () {
    $component = Livewire::test(FlexibleTestComponent::class);

    // rootModelId is not set
    expect(fn () => $component->instance()->modelOptionsFor('country'))
        ->toThrow(LivewireAutoFormException::class, '[Tests\Feature\Livewire\Components\FlexibleTestComponent]');
});

it('throws exception for invalid label mask syntax in models', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.name' => 'required'],
    ]);

    // Invalid mask (contains '(' but no valid '(...)' match)
    expect(fn () => $component->instance()->optionsFor('country', '('))
        ->toThrow(LivewireAutoFormException::class, '[Workbench\App\Models\Country]');
});

it('throws exception for invalid label mask syntax in enums', function () {
    $model = new ModelWithPureEnum;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['pure' => 'required'],
    ]);

    // Invalid mask for enum (must contain (name) or (value))
    expect(fn () => $component->instance()->optionsFor('pure', 'invalid mask'))
        ->toThrow(LivewireAutoFormException::class, '[Tests\Feature\Livewire\Components\FlexibleTestComponent]');
});

it('throws exception if root model class is missing in modelOptionsFor', function () {
    $component = Livewire::test(FlexibleTestComponent::class, [
        'rules' => ['country.name' => 'required'],
    ]);
    $component->instance()->form->rootModelClass = '';
    expect(fn () => $component->instance()->modelOptionsFor('country'))
        ->toThrow(LivewireAutoFormException::class, 'Root model is not set');
});

it('throws exception if root model is not found in modelOptionsFor', function () {
    $component = Livewire::test(FlexibleTestComponent::class, [
        'rules' => ['country.name' => 'required'],
    ]);
    $component->instance()->form->rootModelClass = City::class;
    $component->instance()->form->rootModelId = 99999;
    expect(fn () => $component->instance()->modelOptionsFor('country'))
        ->toThrow(LivewireAutoFormException::class, 'Root model is not set');
});

it('throws exception for non-existent relation in modelOptionsFor', function () {
    $city = City::factory()->create();
    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['ghostRelation.name' => 'required'],
    ]);

    expect(fn () => $component->instance()->modelOptionsFor('ghostRelation'))
        ->toThrow(LivewireAutoFormException::class, 'Relation [ghostRelation] does not exist');
});

it('returns empty array if root model class is missing in enumOptionsFor', function () {
    $component = Livewire::test(FlexibleTestComponent::class);
    $component->instance()->form->rootModelClass = '';
    $options = $component->instance()->enumOptionsFor('status');
    expect($options)->toBe([]);
});

it('returns empty array if root model is not found in enumOptionsFor', function () {
    $component = Livewire::test(FlexibleTestComponent::class);
    $component->instance()->form->rootModelClass = City::class;
    $component->instance()->form->rootModelId = 99999;
    $options = $component->instance()->enumOptionsFor('status');
    expect($options)->toBe([]);
});

it('returns empty array if enum class does not exist in enumOptionsFor', function () {
    Schema::create('model_with_fake_enum', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
    });
    $model = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'model_with_fake_enum';

        public $timestamps = false;

        protected $casts = ['status' => 'integer'];
    };
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->enumOptionsFor('status');
    expect($options)->toBe([]);
});

it('it resolves model options with a column name', function () {
    $country = Country::factory()->create(['name' => 'Specific Name']);
    $city = City::factory()->create(['country_id' => $country->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.name' => 'required'],
    ]);

    // Use 'name' column explicitly
    $options = $component->instance()->optionsFor('country', 'name');

    expect($options)->toBeArray()
        ->and($options)->toContain([$country->id, 'Specific Name']);
});

it('it resolves model options with null label mask (defaults to name)', function () {
    $country = Country::factory()->create(['name' => 'Default Name']);
    $city = City::factory()->create(['country_id' => $country->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['country.name' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('country', null);

    expect($options)->toBeArray()
        ->and($options)->toContain([$country->id, 'Default Name']);
});

it('returns empty array if a generic Throwable occurs in enumOptionsFor', function () {
    $model = City::factory()->create();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    // We need to trigger a Throwable inside enumOptionsFor's try block.
    // Setting rootModelClass to a non-existent class will cause app() to throw BindingResolutionException
    $component->instance()->form->rootModelClass = 'Definitely\NonExistent\Class';

    $options = $component->instance()->enumOptionsFor('status');
    expect($options)->toBe([]);
});

it('throws exception for invalid relation type (HasMany) in modelOptionsFor', function () {
    $model = new ModelWithVariousRelations;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['hasManyRelation.name' => 'required'],
    ]);

    expect(fn () => $component->instance()->modelOptionsFor('hasManyRelation'))
        ->toThrow(LivewireAutoFormException::class, 'is of type [Illuminate\Database\Eloquent\Relations\HasMany] which is not supported');
});

it('throws exception for invalid mask syntax in HandlesOptions trait', function () {
    $model = new ModelWithVariousRelations;
    $model->save();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['belongsToRelation.name' => 'required'],
    ]);

    expect(fn () => $component->instance()->modelOptionsFor('belongsToRelation', '('))
        ->toThrow(LivewireAutoFormException::class, 'Invalid options mask syntax');
});

it('resolves model options with mask in HandlesOptions trait', function () {
    $related = ModelRelated::create(['name' => 'Related Name', 'code' => 'RN']);
    $model = ModelWithVariousRelations::create(['model_related_id' => $related->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['belongsToRelation.name' => 'required'],
    ]);

    $options = $component->instance()->modelOptionsFor('belongsToRelation', '(name) [(code)]');
    expect($options)->toBe([
        [$related->id, 'Related Name [RN]'],
    ]);
});

it('resolves model options without mask in HandlesOptions trait', function () {
    $related = ModelRelated::create(['name' => 'Related Name', 'code' => 'RN']);
    $model = ModelWithVariousRelations::create(['model_related_id' => $related->id]);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['belongsToRelation.name' => 'required'],
    ]);

    $options = $component->instance()->modelOptionsFor('belongsToRelation');
    expect($options)->toBe([
        [$related->id, 'Related Name'],
    ]);
});
