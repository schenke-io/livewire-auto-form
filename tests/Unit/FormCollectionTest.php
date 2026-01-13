<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\FormCollection;

class FormCollectionTest extends TestCase
{
    public function test_nullables_is_read_only_via_magic_set()
    {
        $form = new FormCollection;

        // This should trigger __set and go into items instead of the property
        $form->nullables = ['field1'];

        $reflection = new \ReflectionProperty($form, 'nullables');

        $this->assertEquals([], $reflection->getValue($form));
        $this->assertEquals(['field1'], $form->get('nullables'));
    }

    public function test_nullables_is_readable_via_magic_get()
    {
        $form = new FormCollection;
        $reflection = new \ReflectionProperty($form, 'nullables');
        $reflection->setValue($form, ['field2']);

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
        $this->expectException(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class);
        $form->{FormCollection::SYSTEM_KEY} = 'foo';
    }

    public function test_use_of_system_key_throws_exception_in_put()
    {
        $form = new FormCollection;
        $this->expectException(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class);
        $form->put(FormCollection::SYSTEM_KEY, 'foo');
    }

    public function test_use_of_system_key_throws_exception_in_offset_set()
    {
        $form = new FormCollection;
        $this->expectException(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class);
        $form[FormCollection::SYSTEM_KEY] = 'foo';
    }
}
