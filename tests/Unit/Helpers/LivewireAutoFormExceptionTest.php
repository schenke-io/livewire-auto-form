<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

class LivewireAutoFormExceptionTest extends TestCase
{
    public function test_relation_not_defined_in_rules()
    {
        $e = LivewireAutoFormException::relationNotDefinedInRules('my_relation', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('my_relation', $e->getMessage());
    }

    public function test_field_key_not_defined_in_rules()
    {
        $e = LivewireAutoFormException::fieldKeyNotDefinedInRules('my_key', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('my_key', $e->getMessage());
    }

    public function test_root_model_class_missing()
    {
        $e = LivewireAutoFormException::rootModelClassMissing('MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('Root model class is missing', $e->getMessage());
    }

    public function test_root_model_not_set()
    {
        $e = LivewireAutoFormException::rootModelNotSet('MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('Root model is not set', $e->getMessage());
    }

    public function test_root_model_required()
    {
        $e = LivewireAutoFormException::rootModelRequired('MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('valid root model is required', $e->getMessage());
    }

    public function test_invalid_relation_type()
    {
        $e = LivewireAutoFormException::invalidRelationType('rel', 'type', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('rel', $e->getMessage());
        $this->assertStringContainsString('type', $e->getMessage());
    }

    public function test_relation_does_not_exist()
    {
        $e = LivewireAutoFormException::relationDoesNotExist('rel', 'Model', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('rel', $e->getMessage());
        $this->assertStringContainsString('Model', $e->getMessage());
    }

    public function test_missing_enum_cast()
    {
        $e = LivewireAutoFormException::missingEnumCast('Model', 'attr', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('Model', $e->getMessage());
        $this->assertStringContainsString('attr', $e->getMessage());
    }

    public function test_forbidden_key()
    {
        $e = LivewireAutoFormException::forbiddenKey('key', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('key', $e->getMessage());
    }

    public function test_auto_save_not_allowed_in_wizard()
    {
        $e = LivewireAutoFormException::autoSaveNotAllowedInWizard('MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('Auto-save is not allowed in AutoWizardForm', $e->getMessage());
    }

    public function test_fields_missing_in_steps()
    {
        $e = LivewireAutoFormException::fieldsMissingInSteps(['field1', 'field2'], 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('field1, field2', $e->getMessage());
    }

    public function test_wizard_view_not_found()
    {
        $e = LivewireAutoFormException::wizardViewNotFound('view_name', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('view_name', $e->getMessage());
    }

    public function test_options_mask_syntax()
    {
        $e = LivewireAutoFormException::optionsMaskSyntax('mask', 'MyOrigin');
        $this->assertStringContainsString('[MyOrigin]', $e->getMessage());
        $this->assertStringContainsString('mask', $e->getMessage());
    }
}
