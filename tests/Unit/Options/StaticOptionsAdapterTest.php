<?php

use SchenkeIo\LivewireAutoForm\Options\OptionsFactory;
use Tests\TestCase;

uses(TestCase::class);

it('maps simple scalar options', function () {
    $resolver = OptionsFactory::forArray([
        'a' => 'Alpha',
        2 => 'Two',
    ]);

    $options = $resolver->getOptions();

    expect($options)->toContain(['a', 'Alpha'])
        ->and($options)->toContain([2, 'Two']);
});

it('maps icon array options [label, icon]', function () {
    $resolver = OptionsFactory::forArray([
        'one' => ['One', 'icon-one'],
    ]);

    $options = $resolver->getOptions();

    expect($options)->toContain(['one', 'One', 'icon-one']);
});

it('maps key/replace structured arrays', function () {
    $resolver = OptionsFactory::forArray([
        'x' => ['key' => 'Hello :name', 'replace' => ['name' => 'World']],
    ]);

    $options = $resolver->getOptions();

    expect($options)->toContain(['x', 'Hello World']);
});
