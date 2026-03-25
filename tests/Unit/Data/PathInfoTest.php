<?php

use SchenkeIo\LivewireAutoForm\Data\PathInfo;

it('joins relationChain into a dotted path', function () {
    $info = new PathInfo(['user', 'profile'], 'name');
    expect($info->getRelationPath())->toBe('user.profile');
});

it('returns empty string for empty relationChain', function () {
    $info = new PathInfo([], 'name');
    expect($info->getRelationPath())->toBe('');
});
