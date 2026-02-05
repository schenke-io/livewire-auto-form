<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use PHPUnit\Framework\TestCase;
use SchenkeIo\Invoice\Casts\CurrencyCast;
use SchenkeIo\Invoice\Money\Currency;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;

enum BackedEnumTest: string
{
    case Alpha = 'alpha';
}

enum UnitEnumTest
{
    case Beta;
}

class EnumModelTest extends Model
{
    protected $guarded = [];
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

    public function test_sanitize_nullable_value()
    {
        $processor = new DataProcessor;
        $this->assertNull($processor->sanitizeValue('email', '', ['email']));
        $this->assertEquals('trimmed', $processor->sanitizeValue('key', ' trimmed ', []));
    }
}
