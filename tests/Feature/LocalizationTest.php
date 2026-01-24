<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;

enum EnumWithTrait: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;
    case ONE = '1';
}

enum EnumWithTraitAndPrefix: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    const OPTION_TRANSLATION_PREFIX = 'custom.prefix';
    case TWO = '2';
}

enum LocalizationEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

class LocalizationModel extends Model
{
    protected $casts = [
        'status' => LocalizationEnum::class,
    ];
}

enum LocalisedEnumWithOptions: string implements AutoFormOptions
{
    case ACTIVE = 'active';

    public static function getOptions(?string $labelMask = null): array
    {
        return [
            self::ACTIVE->value => 'enums.status.active',
        ];
    }
}

class LocalisedModelWithOptions extends Model
{
    protected $casts = [
        'status' => LocalisedEnumWithOptions::class,
    ];
}

class ModelWithReplacements extends Model implements AutoFormOptions
{
    public static function getOptions(?string $labelMask = null): array
    {
        return [
            'active' => ['key' => 'test.active_count', 'replace' => ['count' => 5]],
        ];
    }
}

class ContainerModel extends Model
{
    public function replacement()
    {
        return $this->belongsTo(ModelWithReplacements::class, 'replacement_id');
    }
}

it('translates enum fallback labels', function () {
    Schema::create('localization_models', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    $model = new LocalizationModel;
    $model->save();

    // Set up translation
    app('translator')->addLines(['test.DRAFT' => 'Le Draft'], 'fr');
    app()->setLocale('fr');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status', 'test.(name)');

    // Expected: [[ 'draft', 'Le Draft' ], ...]
    expect($options[0][1])->toBe('Le Draft');
});

it('translates labels from AutoFormOptions implementation', function () {
    Schema::create('localised_model_with_options', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    $model = new LocalisedModelWithOptions;
    $model->save();

    // Set up translation
    app('translator')->addLines(['enums.status.active' => 'Actif'], 'fr');
    app()->setLocale('fr');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status');

    expect($options[0][1])->toBe('Actif');
});

it('translates labels with replacements from AutoFormOptions', function () {
    Schema::create('container_models', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('replacement_id')->nullable();
        $table->timestamps();
    });
    Schema::create('model_with_replacements', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    $model = new ContainerModel;
    $model->save();

    // Set up translation
    app('translator')->addLines(['test.active_count' => ':count Active'], 'en');
    app()->setLocale('en');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['replacement.id' => 'required'],
    ]);

    // Override the return of getOptions to use our translation key
    // Actually, I'll just change the ModelWithReplacements class above
    // to use test.active_count instead of enums.status.active_count

    $options = $component->instance()->optionsFor('replacement');

    expect($options[0][1])->toBe('5 Active');
});

it('uses AutoFormLocalisedOptions trait for automatic translation keys', function () {
    $model = new class extends Model
    {
        protected $casts = ['status' => EnumWithTrait::class];
    };

    // Default prefix is snake_case of class name: enum_with_trait
    app('translator')->addLines(['enum_with_trait.1' => 'Translated One'], 'en');
    app()->setLocale('en');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status');
    expect($options[0][1])->toBe('Translated One');
});

it('uses custom prefix in AutoFormLocalisedOptions trait', function () {
    $model = new class extends Model
    {
        protected $casts = ['status' => EnumWithTraitAndPrefix::class];
    };

    app('translator')->addLines(['custom.prefix.2' => 'Translated Two'], 'en');
    app()->setLocale('en');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status');
    expect($options[0][1])->toBe('Translated Two');
});

it('allows overriding prefix via labelMask in AutoFormLocalisedOptions trait', function () {
    $model = new class extends Model
    {
        protected $casts = ['status' => EnumWithTraitAndPrefix::class];
    };

    app('translator')->addLines(['override.2' => 'Overridden Two'], 'en');
    app()->setLocale('en');

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $model,
        'rules' => ['status' => 'required'],
    ]);

    $options = $component->instance()->optionsFor('status', 'override');
    expect($options[0][1])->toBe('Overridden Two');
});
