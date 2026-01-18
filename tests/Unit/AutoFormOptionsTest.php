<?php

use SchenkeIo\LivewireAutoForm\AutoFormOptions;

test('AutoFormOptions exists and has getOptions method', function () {
    expect(interface_exists(AutoFormOptions::class))->toBeTrue();

    $reflection = new ReflectionClass(AutoFormOptions::class);
    expect($reflection->hasMethod('getOptions'))->toBeTrue();

    $method = $reflection->getMethod('getOptions');
    expect($method->isStatic())->toBeTrue();
    expect($method->isPublic())->toBeTrue();

    $parameters = $method->getParameters();
    expect(count($parameters))->toBe(1);
    expect($parameters[0]->getName())->toBe('labelMask');
    expect($parameters[0]->allowsNull())->toBeTrue();
});
