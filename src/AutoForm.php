<?php

namespace SchenkeIo\LivewireAutoForm;

use SchenkeIo\LivewireAutoForm\Helpers\BaseAutoForm;

/**
 * USAGE GUIDE:
 * 1. COMPONENT: Extend 'AutoForm' in your component class.
 * 2. INITIALIZATION: Call '$this->setModel($model);' in mount().
 * 3. RULES: Define 'public function rules()' in your component; they are automatically used.
 * 4. BINDING: Use 'wire:model="form.field"' for root attributes and 'wire:model="form.relation.field"' for relationships.
 * 5. SELECTS: Call '$this->optionsFor("field")' in Blade to generate value/label pairs for Enums or Eloquent relations.
 * 6. INTERFACE: For custom labels, implement 'SchenkeIo\LivewireAutoForm\AutoFormOptions' on your Model or BackedEnum.
 * 7. SAVING: Use 'wire:submit="save"' for batch updates or toggle '$this->autoSave' for real-time persistence.
 */

/**
 * AutoForm is the core of the Livewire Auto Form package.
 *
 * It is now a base Component that manages the lifecycle of form form, including:
 * - Buffer management for Eloquent models and their relationships.
 * - Automatic loading and saving of form based on defined validation rules.
 * - Dynamic context switching between root models and related models (e.g. for sub-forms).
 * - Integration with Livewire's validation system with automatic error prefixing.
 * - Helpers for generating option arrays for select inputs from Enums or Models.
 * - Support for real-time "auto-save" functionality.
 *
 * It abstracts away the tedious form mapping and relationship handling by
 * providing a robust buffering system directly within the component.
 */
class AutoForm extends BaseAutoForm {}
