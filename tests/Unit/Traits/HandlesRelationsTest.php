<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Traits\HandlesRelations;

class HandlesRelationsTest extends TestCase
{
    private $testClass;

    protected function setUp(): void
    {
        $this->testClass = new class
        {
            use HandlesRelations;

            public FormCollection $form;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function rules(): array
            {
                return ['rel.field' => 'required'];
            }

            public function guardDirtyBuffer() {}

            public function loadContext($relation, $id) {}

            public function getCrudProcessor()
            {
                return new class
                {
                    public function getRelationList($rel, $rules)
                    {
                        return new Collection(['item']);
                    }
                };
            }
        };
    }

    public function test_set_context()
    {
        // Lines 96-98
        $this->testClass->setContext('new_context', 123);
        $this->assertEquals('new_context', $this->testClass->activeContext);
        $this->assertEquals(123, $this->testClass->activeId);
        $this->assertEquals('new_context', $this->testClass->form->activeContext);
        $this->assertEquals(123, $this->testClass->form->activeId);
    }

    public function test_is_relation_allowed_with_empty_relation()
    {
        // Line 110
        $this->assertFalse($this->testClass->isRelationAllowed(''));
    }

    public function test_is_relation_allowed_with_disallowed_relation()
    {
        // Line 120
        $this->assertFalse($this->testClass->isRelationAllowed('other_rel'));
    }

    public function test_ensure_relation_allowed_throws_exception()
    {
        // Line 131
        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('[class@anonymous');
        $this->testClass->ensureRelationAllowed('disallowed');
    }

    public function test_get_relation_list()
    {
        // Lines 155-157
        $list = $this->testClass->getRelationList('rel');
        $this->assertInstanceOf(Collection::class, $list);
        $this->assertCount(1, $list);
    }
}
