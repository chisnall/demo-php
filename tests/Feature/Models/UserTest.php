<?php

use App\Models\User;

test('has correct fillable attributes', function () {
    expect((new User)->getFillable())
        ->toBe(['client_id', 'name', 'email', 'password']);
});

test('has correct hidden attributes', function () {
    expect((new User)->getHidden())
        ->toBe(['password', 'remember_token']);
});

test('casts email_verified_at as datetime', function () {
    expect((new User)->getCasts())
        ->toHaveKey('email_verified_at', 'datetime');
});

test('casts password as hashed', function () {
    expect((new User)->getCasts())
        ->toHaveKey('password', 'hashed');
});

test('hashes password on set', function () {
    $user = new User(['password' => 'plain-text']);

    expect(Hash::isHashed($user->password))->toBeTrue()
        ->and(Hash::check('plain-text', $user->password))->toBeTrue();
});
