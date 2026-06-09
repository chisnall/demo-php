<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'id' => 1,
        'client_id' => 5001,
    ]);

    $this->data = [
        'depot_id' => 101,
        'fleet_size' => 12,
    ];

    $this->endpoint = '/api/variation/fleet';
});

test('Validation fails when fields are missing', function () {
    $this->actingAs($this->user);

    $response = $this->postJson($this->endpoint);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'depot_id' => ['An operating centre depot ID must be provided.'],
            'fleet_size' => ['You must specify the new vehicle fleet size.'],
        ]);
});

test('Validation fails when data types are incorrect', function () {
    $this->actingAs($this->user);

    $this->data['depot_id'] = 'invalid';
    $this->data['fleet_size'] = 12.5;

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'depot_id' => ['The depot ID must be a valid numeric identifier.'],
            'fleet_size' => ['The requested vehicle size fleet must be a whole number.'],
        ]);
});

test('Validation passes', function () {
    Config::set('services.typescript_api.url', 'http://api');

    Http::fake([
        'http://api/variation' => Http::response([
            'success' => true,
            'message' => 'API request successful.',
            'data' => [
                'status' => 'approved',
            ],
        ]),
    ]);

    $this->actingAs($this->user);

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(202);
});
