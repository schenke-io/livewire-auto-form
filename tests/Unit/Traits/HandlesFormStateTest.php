<?php

namespace Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Traits\HandlesFormState;

class HandlesFormStateTest extends TestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class implements \ArrayAccess
        {
            use HandlesFormState;

            public function getAutoSave()
            {
                return $this->autoSave;
            }
        };
        $this->testClass->bootHandlesFormState();
    }

    public function test_initialization()
    {
        $this->assertInstanceOf(FormCollection::class, $this->testClass->form);
    }

    public function test_initialize_has_auto_form()
    {
        $obj = new class implements \ArrayAccess
        {
            use HandlesFormState;
        };
        $obj->initializeHasAutoForm();
        $this->assertInstanceOf(FormCollection::class, $obj->form);
    }

    public function test_offset_exists()
    {
        $this->testClass->form->put('name', 'John');
        $this->assertTrue(isset($this->testClass['name']));
        $this->assertTrue(isset($this->testClass['autoSave']));
        $this->assertTrue(isset($this->testClass['activeContext']));
        $this->assertFalse(isset($this->testClass['non_existent']));
    }

    public function test_offset_get()
    {
        $this->testClass->form->put('name', 'John');
        $this->assertEquals('John', $this->testClass['name']);
    }

    public function test_offset_set_routes_to_property()
    {
        // autoSave is a protected property in the trait
        $this->testClass['autoSave'] = true;
        $this->assertTrue($this->testClass->getAutoSave());
        $this->assertFalse($this->testClass->form->has('autoSave'));
    }

    public function test_offset_set_routes_to_form_buffer()
    {
        $this->testClass['email'] = 'john@example.com';
        $this->assertEquals('john@example.com', $this->testClass->form->get('email'));
    }

    public function test_offset_set_handles_nested()
    {
        $this->testClass['address.city'] = 'New York';
        $this->assertEquals(['city' => 'New York'], $this->testClass->form->get('address'));
    }

    public function test_offset_unset()
    {
        $this->testClass->form->put('name', 'John');
        unset($this->testClass['name']);
        $this->assertFalse($this->testClass->form->has('name'));
    }

    public function test_all_returns_everything()
    {
        $this->testClass->form->put('name', 'John');
        $this->testClass->form->put('age', 30);
        $this->assertEquals(['name' => 'John', 'age' => 30], $this->testClass->all());
    }

    public function test_get_with_default()
    {
        $this->testClass->form->put('name', 'John');
        $this->assertEquals('John', $this->testClass->get('name'));
        $this->assertEquals('Default', $this->testClass->get('missing', 'Default'));
    }

    public function test_has()
    {
        $this->testClass->form->put('name', 'John');
        $this->assertTrue($this->testClass->has('name'));
        $this->assertFalse($this->testClass->has('missing'));
    }
}
