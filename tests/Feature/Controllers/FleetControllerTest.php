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

test('variation request - fails - not authenticated', function () {
    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(401);
});

test('variation request - fails - missing fields', function () {
    $this->actingAs($this->user);

    $response = $this->postJson($this->endpoint);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'depot_id' => ['An operating centre depot ID must be provided.'],
            'fleet_size' => ['You must specify the new vehicle fleet size.'],
        ]);
});

test('variation request - fails - depot is missing', function () {
    $this->actingAs($this->user);

    unset($this->data['depot_id']);

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'depot_id' => ['An operating centre depot ID must be provided.'],
        ]);
});

test('variation request - fails - size is missing', function () {
    $this->actingAs($this->user);

    unset($this->data['fleet_size']);

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'fleet_size' => ['You must specify the new vehicle fleet size.'],
        ]);
});

test('variation request - fails - invalid depot', function () {
    $this->actingAs($this->user);

    $this->data['depot_id'] = 'invalid';

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'depot_id' => ['The depot ID must be a valid numeric identifier.'],
        ]);
});

test('variation request - fails - invalid size', function () {
    $this->actingAs($this->user);

    $this->data['fleet_size'] = 'invalid';

    $response = $this->postJson($this->endpoint, $this->data);

    expect($response->status())->toBe(422)
        ->and($response->json('errors'))->toMatchArray([
            'fleet_size' => ['The requested vehicle size fleet must be a whole number.'],
        ]);
});

test('variation passes', function () {
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

    expect($response->status())->toBe(202)
        ->and($response->json('success'))->toBeTrue()
        ->and($response->json())->toHaveKey('data');
});
