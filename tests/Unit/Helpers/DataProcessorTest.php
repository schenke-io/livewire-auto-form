<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;

class DataProcessorTest extends TestCase
{
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
