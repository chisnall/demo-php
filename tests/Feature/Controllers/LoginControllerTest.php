<?php

use App\Models\User;

beforeEach(function () {
    $this->email = 'user@email.com';

    $this->password = 'abc123';

    $this->endpoint = '/api/login';
});

test('login fails - no credentials', function () {
    $response = $this->postJson($this->endpoint);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);
});

test('login fails - email is missing', function () {
    $response = $this->postJson($this->endpoint, [
        'password' => $this->password,
    ]);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'email' => ['The email field is required.'],
        ]);
});

test('login fails - password is missing', function () {
    $response = $this->postJson($this->endpoint, [
        'email' => $this->email,
    ]);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'password' => ['The password field is required.'],
        ]);
});

test('login fails - invalid user', function () {
    $response = $this->postJson($this->endpoint, [
        'email' => $this->email,
        'password' => $this->password,
    ]);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'email' => ['The provided credentials are incorrect.'],
        ]);
});

test('login passes - valid user', function () {
    User::factory()->create([
        'email' => $this->email,
        'password' => bcrypt($this->password),
    ]);

    $response = $this->postJson($this->endpoint, [
        'email' => $this->email,
        'password' => $this->password,
    ]);

    expect($response->status())->toBe(200)
        ->and($response->json())->toHaveKey('token')
        ->and($response->json('token'))->toBeString();
});
