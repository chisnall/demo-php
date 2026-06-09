<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'id' => 1,
        'client_id' => 5001,
        'email' => 'user@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->data = [
        'email' => 'user@example.com',
        'password' => 'password',
    ];

    $this->endpoint = '/api/login';
});

test('Validation fails when fields are missing', function () {
    $response = $this->postJson($this->endpoint);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);
});

test('Validation fails when data types are incorrect', function () {
    $this->data['email'] = 'invalid';
    $this->data['password'] = 12.5;

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'email' => ['The email field must be a valid email address.'],
            'password' => ['The password field must be a string.'],
        ]);
});

test('Validation passes', function () {
    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(200);
});
