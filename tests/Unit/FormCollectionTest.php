<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

class FormCollectionTest extends TestCase
{
    public function test_nullables_is_accessible_via_magic_set()
    {
        $form = new FormCollection;

        $form->nullables = ['field1'];

        $this->assertEquals(['field1'], $form->nullables);
        $this->assertEquals(['field1'], $form->meta['nullables']);
        // it should NOT go into items
        $this->assertFalse($form->has('nullables'));
    }

    public function test_nullables_is_readable_via_magic_get()
    {
        $form = new FormCollection;
        $form->setNullables(['field2']);

        $this->assertEquals(['field2'], $form->nullables);
    }

    public function test_to_and_from_livewire_conversion()
    {
        $form = new FormCollection(['name' => 'John']);
        $form->setContext('test', 123);
        $form->setNullables(['email']);

        $livewireData = $form->toLivewire();

        $this->assertArrayHasKey(FormCollection::SYSTEM_KEY, $livewireData);
        $this->assertEquals('John', $livewireData['name']);
        $this->assertEquals('test', $livewireData[FormCollection::SYSTEM_KEY]['activeContext']);
        $this->assertEquals(123, $livewireData[FormCollection::SYSTEM_KEY]['activeId']);
        $this->assertEquals(['email'], $livewireData[FormCollection::SYSTEM_KEY]['nullables']);

        $restoredForm = FormCollection::fromLivewire($livewireData);

        $this->assertInstanceOf(FormCollection::class, $restoredForm);
        $this->assertEquals('John', $restoredForm->get('name'));
        $this->assertEquals('test', $restoredForm->activeContext);
        $this->assertEquals(123, $restoredForm->activeId);
        $this->assertEquals(['email'], $restoredForm->nullables);
        $this->assertFalse($restoredForm->has(FormCollection::SYSTEM_KEY));
    }

    public function test_use_of_system_key_throws_exception_in_set()
    {
        $form = new FormCollection;
        $this->expectException(\SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\FormCollection]');
        $form->{FormCollection::SYSTEM_KEY} = 'foo';
    }

    public function test_use_of_system_key_throws_exception_in_put()
    {
        $form = new FormCollection;
        $this->expectException(\SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\FormCollection]');
        $form->put(FormCollection::SYSTEM_KEY, 'foo');
    }

    public function test_use_of_system_key_throws_exception_in_offset_set()
    {
        $form = new FormCollection;
        $this->expectException(\SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\FormCollection]');
        $form[FormCollection::SYSTEM_KEY] = 'foo';
    }

    public function test_clear_data_preserves_state()
    {
        $form = new FormCollection(['name' => 'John']);
        $form->setContext('test', 123);

        $form->clearData();

        $this->assertEmpty($form->all());
        $this->assertEquals('test', $form->activeContext);
        $this->assertEquals(123, $form->activeId);
    }

    public function test_magic_set_for_non_meta_property_goes_to_items()
    {
        $form = new FormCollection;
        $form->some_key = 'some_value';

        $this->assertEquals('some_value', $form->get('some_key'));
    }

    public function test_magic_getters_for_meta()
    {
        $form = new FormCollection;
        $form->setContext('ctx', 789);
        $form->rootModelClass = 'MyModel';
        $form->rootModelId = 101;

        $this->assertEquals('ctx', $form->activeContext);
        $this->assertEquals(789, $form->activeId);
        $this->assertEquals('MyModel', $form->rootModelClass);
        $this->assertEquals(101, $form->rootModelId);
        $this->assertNull($form->non_existent);
    }

    public function test_magic_setters_for_meta()
    {
        $form = new FormCollection;
        $form->activeContext = 'new_ctx';
        $form->activeId = 111;
        $form->rootModelClass = 'OtherModel';
        $form->rootModelId = 222;
        $form->autoSave = true;

        $this->assertEquals('new_ctx', $form->activeContext);
        $this->assertEquals(111, $form->activeId);
        $this->assertEquals('OtherModel', $form->rootModelClass);
        $this->assertEquals(222, $form->rootModelId);
        $this->assertTrue($form->autoSave);
    }

    public function test_offset_unset()
    {
        $form = new FormCollection(['name' => 'John']);
        unset($form['name']);
        $this->assertFalse($form->has('name'));
    }

    public function test_explicit_getters_and_setters()
    {
        $form = new FormCollection;
        $form->setContext('ctx', 1);
        $form->setRootModel('Model', 2);
        $form->setNullables(['a']);
        $form->setAutoSave(true);

        $this->assertEquals('ctx', $form->getActiveContext());
        $this->assertEquals(1, $form->getActiveId());
        $this->assertEquals('Model', $form->getRootModelClass());
        $this->assertEquals(2, $form->getRootModelId());
        $this->assertEquals(['a'], $form->getNullables());
        $this->assertTrue($form->isAutoSave());
        $this->assertFalse($form->isRoot());

        $form->setContext('', null);
        $this->assertTrue($form->isRoot());
    }

    public function test_count_and_iterator()
    {
        $form = new FormCollection(['a' => 1, 'b' => 2]);
        $this->assertCount(2, $form);
        $items = iterator_to_array($form);
        $this->assertEquals(['a' => 1, 'b' => 2], $items);
    }

    public function test_set_nested_with_non_array_base()
    {
        $form = new FormCollection;
        $form->put('relation', 'string');
        $form->setNested('relation.field', 'value');
        $this->assertEquals(['field' => 'value'], $form->get('relation'));
    }

    public function test_set_nested_simple_key()
    {
        $form = new FormCollection;
        $form->setNested('simple', 'value');
        $this->assertEquals('value', $form->get('simple'));
    }

    public function test_magic_set_with_dot_notation()
    {
        $form = new FormCollection;
        $form->{'relation.field'} = 'value';
        $this->assertEquals(['field' => 'value'], $form->get('relation'));
    }
}
