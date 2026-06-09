<?php

use App\Services\TypeScriptApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->baseUrl = 'http://server/api';
    $this->accessKey = 'test_access_key';
    $this->secretKey = 'test_secret_key';

    $this->client = new TypeScriptApiClient(
        $this->baseUrl,
        $this->accessKey,
        $this->secretKey
    );

    $this->data = [
        'depot_id' => 101,
        'fleet_size' => 15,
    ];

    $this->endpoint = $this->baseUrl.'/variation';
});

test('sendEvent - returns false and logs error on failed response status codes', function () {
    Http::fake([
        $this->endpoint => Http::response(['error' => 'Invalid Keys'], 401),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with('TypeScript API response | 401 | {"error":"Invalid Keys"}');

    $result = $this->client->sendEvent($this->data);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'API error 401',
    ]);
});

test('sendEvent - returns false and logs critical on connection exceptions', function () {
    Http::fake([
        $this->endpoint => function () {
            throw new ConnectionException('Connection timed out after 5000ms');
        },
    ]);

    Log::shouldReceive('critical')
        ->once()
        ->with('TypeScript API response | Connection timed out after 5000ms');

    $result = $this->client->sendEvent($this->data);

    expect($result)->toMatchArray([
        'success' => false,
        'message' => 'API error - unable to contact API.',
    ]);
});

test('sendEvent - returns true and logs info on successful request', function () {
    Http::fake([
        $this->endpoint => Http::response([
            'success' => true,
            'message' => 'API request successful.',
            'data' => [
                'status' => 'approved',
            ],
        ]),
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('TypeScript API response | 200 | message: API request successful. | status: approved');

    $result = $this->client->sendEvent($this->data);

    expect($result)->toMatchArray([
        'success' => true,
        'message' => 'API request successful.',
    ]);

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('X-API-Access-Key', $this->accessKey)
            && $request->hasHeader('X-API-Secret-Key', $this->secretKey)
            && $request->hasHeader('Accept', 'application/json')
            && $request['depot_id'] === 101;
    });
});
