<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Stringable;
use SchenkeIo\Invoice\Casts\CurrencyCast;
use SchenkeIo\Invoice\Money\Currency;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use Tests\TestCase;

class RelatedModelTest extends Model
{
    public function getKeyName(): string
    {
        return 'custom_pk';
    }
}

class ModelWithCustomFK extends Model
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(RelatedModelTest::class, 'profile_custom_fk', 'custom_pk');
    }

    public function isRelation($method): bool
    {
        return $method === 'profile';
    }
}

enum BackedEnumTest: string
{
    case Alpha = 'alpha';
}

enum UnitEnumTest
{
    case Beta;
}

enum IntBackedEnumTest: int
{
    case One = 1;
}

class ModelWithIntEnumTest extends Model
{
    protected $casts = [
        'status' => IntBackedEnumTest::class,
    ];
}

class EnumModelTest extends Model
{
    protected $guarded = [];
}

class JsonCastModelTest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];
}

class DataProcessorTest extends TestCase
{
    public function test_extract_filtered_data_flattens_enums_and_stringable_and_currency()
    {
        $processor = new DataProcessor;
        $stringable = new Stringable('hello');
        $currency = Currency::fromAny('12,34 €');
        $model = new EnumModelTest([
            'backed' => BackedEnumTest::Alpha,
            'unit' => UnitEnumTest::Beta,
            'note' => $stringable,
            'price' => $currency,
        ]);

        $rules = [
            'backed' => 'required',
            'unit' => 'required',
            'note' => 'sometimes',
            'price' => CurrencyCast::class,
        ];

        $data = $processor->extractFilteredData($model, $rules, '');

        $this->assertEquals('alpha', $data['backed']);
        $this->assertEquals('Beta', $data['unit']);
        $this->assertEquals('hello', $data['note']);
        $this->assertEquals(12.34, $data['price']);
    }

    public function test_sanitize_value_flattens_enums_and_currency()
    {
        $processor = new DataProcessor;

        $backed = $processor->sanitizeValue('key', BackedEnumTest::Alpha, []);
        $unit = $processor->sanitizeValue('key', UnitEnumTest::Beta, []);
        $currency = $processor->sanitizeValue('price', Currency::fromFloat(12.34), []);

        $this->assertEquals('alpha', $backed);
        $this->assertEquals('Beta', $unit);
        $this->assertEquals(12.34, $currency);
    }

    public function test_find_relations_identifies_nested_relations()
    {
        $processor = new DataProcessor;
        $rules = [
            'name' => 'required',
            'profile.phone' => 'required',
            'profile.address.street' => 'required',
            'tags.name' => 'required',
        ];

        $relations = $processor->findRelations($rules);

        $this->assertContains('profile', $relations);
        $this->assertContains('tags', $relations);
        // This is what we might want for nested relations
        // $this->assertContains('profile.address', $relations);
    }

    public function test_get_allowed_fields_includes_nested_paths_in_root()
    {
        $processor = new DataProcessor;
        $rules = [
            'profile.address.street' => 'required',
        ];

        $allowed = $processor->getAllowedFields($rules, '');
        $this->assertContains('profile.address.street', $allowed);
        $this->assertContains('profile_id', $allowed);
    }

    public function test_get_allowed_fields_includes_nested_id_fields_for_sub_context()
    {
        $processor = new DataProcessor;
        $rules = [
            'profile.address.street' => 'required',
        ];

        $allowed = $processor->getAllowedFields($rules, 'profile');
        $this->assertContains('address.street', $allowed);
        // This currently fails because getAllowedFields only adds _id in root context
        $this->assertContains('address_id', $allowed);
    }

    public function test_get_allowed_fields_handles_form_prefix()
    {
        $processor = new DataProcessor;
        $rules = [
            'form.name' => 'required',
            'form.cities.name' => 'required',
        ];

        $allowed = $processor->getAllowedFields($rules, '');
        $this->assertContains('name', $allowed);
        $this->assertContains('cities.name', $allowed);
    }

    public function test_find_nullables_with_array_rules()
    {
        $processor = new DataProcessor;
        $rules = [
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ];

        $nullables = $processor->findNullables($rules);
        $this->assertNotContains('name', $nullables);
        $this->assertContains('email', $nullables);
    }

    public function test_sanitize_value_handles_non_strings()
    {
        $processor = new DataProcessor;
        $this->assertEquals(123, $processor->sanitizeValue('key', 123, []));
        $this->assertEquals(true, $processor->sanitizeValue('key', true, []));
    }

    public function test_get_allowed_fields_uses_custom_foreign_key()
    {
        $processor = new DataProcessor;
        $model = new ModelWithCustomFK;
        $rules = [
            'profile.name' => 'required',
        ];

        $allowed = $processor->getAllowedFields($rules, '', $model);

        $this->assertContains('profile_custom_fk', $allowed);
        $this->assertNotContains('profile_id', $allowed);
    }

    public function test_get_allowed_fields_handles_nullable_model()
    {
        $processor = new DataProcessor;
        $rules = [
            'profile.name' => 'required',
        ];

        $allowed = $processor->getAllowedFields($rules, '', null);

        $this->assertContains('profile_id', $allowed);
    }

    public function test_find_relations_identifies_relations_only_if_is_relation_is_true()
    {
        $processor = new DataProcessor;
        $rules = [
            'features.vin' => 'required',
        ];
        $model = new JsonCastModelTest;

        // Currently it identifies 'features' as a relation because it sees a dot
        $relations = $processor->findRelations($rules, '');

        $this->assertContains('features', $relations);

        // With model it should NOT contain 'features'
        $relations = $processor->findRelations($rules, '', $model);
        $this->assertNotContains('features', $relations);
    }

    public function test_get_allowed_fields_does_not_add_id_suffix_for_non_relations()
    {
        $processor = new DataProcessor;
        $rules = [
            'features.vin' => 'required',
        ];
        $model = new JsonCastModelTest;

        $allowed = $processor->getAllowedFields($rules, '', $model);

        $this->assertNotContains('features_id', $allowed);
    }

    public function test_data_processor_identifies_json_columns()
    {
        $processor = new DataProcessor;
        $rules = [
            'name' => 'required',
            'settings' => 'json_column|nullable',
            'meta' => ['required', 'json_column'],
        ];

        $jsonColumns = $processor->findJsonColumns($rules);

        $this->assertContains('settings', $jsonColumns);
        $this->assertContains('meta', $jsonColumns);
        $this->assertNotContains('name', $jsonColumns);
    }

    public function test_data_processor_translates_paths_for_explicit_json_columns()
    {
        $processor = new DataProcessor;
        $jsonColumns = ['settings', 'meta'];

        $this->assertEquals('settings->theme', $processor->translatePath('settings.theme', $jsonColumns));
        $this->assertEquals('meta->color->primary', $processor->translatePath('meta.color.primary', $jsonColumns));
        $this->assertEquals('name', $processor->translatePath('name', $jsonColumns));
    }

    public function test_data_processor_translates_paths_for_casted_columns()
    {
        $processor = new DataProcessor;
        $model = new JsonCastModelTest;

        // No explicit json columns
        $jsonColumns = [];

        $this->assertEquals('settings->theme', $processor->translatePath('settings.theme', $jsonColumns, $model));
        $this->assertEquals('other.field', $processor->translatePath('other.field', $jsonColumns)); // No model, no translation
    }

    public function test_data_processor_excludes_json_columns_from_relations()
    {
        $processor = new DataProcessor;
        $rules = [
            'settings' => 'json_column',
            'settings.theme' => 'required',
            'profile.phone' => 'required',
        ];

        $relations = $processor->findRelations($rules);

        $this->assertContains('profile', $relations);
        $this->assertNotContains('settings', $relations);
    }

    public function test_get_allowed_fields_handles_pivot_relation()
    {
        $processor = new DataProcessor;
        $model = new class extends Model
        {
            public function pivot()
            {
                return $this->belongsTo(self::class);
            }

            public function isRelation($method): bool
            {
                return $method === 'pivot';
            }
        };
        $rules = ['form.pivot.status' => 'string'];
        $fields = $processor->getAllowedFields($rules, '', $model);
        $this->assertNotContains('pivot_id', $fields);
    }

    public function test_get_allowed_fields_handles_bad_method_call_exception()
    {
        $processor = new DataProcessor;
        $model = new class extends Model
        {
            public function broken()
            {
                throw new \BadMethodCallException;
            }

            public function isRelation($method): bool
            {
                return $method === 'broken';
            }
        };
        $rules = ['form.broken.name' => 'string'];
        $fields = $processor->getAllowedFields($rules, '', $model);
        $this->assertNotContains('broken_id', $fields);
    }

    public function test_sanitize_value_returns_null_for_nullable_empty_string()
    {
        $processor = new DataProcessor;
        $this->assertNull($processor->sanitizeValue('field', '', ['field']));
    }

    public function test_translate_path_returns_exact_match_for_json_column()
    {
        $processor = new DataProcessor;
        $this->assertEquals('data', $processor->translatePath('data', ['data']));
    }

    public function test_sanitize_value_casts_to_int_for_int_backed_enums()
    {
        $processor = new DataProcessor;
        $model = new ModelWithIntEnumTest;

        // Numeric string should be cast to int
        $this->assertSame(1, $processor->sanitizeValue('status', '1', [], $model));

        // Non-numeric string should remain string (though ReflectionEnum might catch it later if it's not a valid case)
        $this->assertSame('abc', $processor->sanitizeValue('status', 'abc', [], $model));

        // Numeric string for a key NOT in casts should remain string
        $this->assertSame('1', $processor->sanitizeValue('other', '1', [], $model));

        // No model provided - should remain string
        $this->assertSame('1', $processor->sanitizeValue('status', '1', []));
    }

    public function test_sanitize_value_does_not_cast_to_int_for_string_backed_enums()
    {
        $processor = new DataProcessor;
        $model = new class extends Model
        {
            protected $casts = ['type' => BackedEnumTest::class];
        };

        // 'alpha' is string-backed, so '1' should NOT be cast to int
        $this->assertSame('1', $processor->sanitizeValue('type', '1', [], $model));
    }

    public function test_extract_filtered_data_includes_id_if_model_exists_and_id_missing()
    {
        $processor = new DataProcessor;
        $model = new EnumModelTest(['id' => 123]);
        $model->exists = true; // Simulate existing model

        $rules = ['backed' => 'required'];
        $model->backed = BackedEnumTest::Alpha;

        $data = $processor->extractFilteredData($model, $rules, '');

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals(123, $data['id']);
    }

    public function test_sanitize_value_does_not_cast_to_int_for_unit_enums()
    {
        $processor = new DataProcessor;
        $model = new class extends Model
        {
            protected $casts = ['type' => UnitEnumTest::class];
        };

        // Unit enum is not backed, so should not cast
        $this->assertSame('1', $processor->sanitizeValue('type', '1', [], $model));
    }

    public function test_sanitize_value_handles_nested_keys()
    {
        $processor = new DataProcessor;
        $model = new ModelWithIntEnumTest;

        // Nested key should be correctly handled by stripping prefix
        $this->assertSame(1, $processor->sanitizeValue('profile.status', '1', [], $model));
    }
}
