<?php

use App\Interfaces\ApiClient;
use App\Jobs\DispatchEventJob;
use App\Models\Variation;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

beforeEach(function () {
    $this->payload = [
        'client_id' => 5001,
        'type' => 'variation',
        'subtype' => 'fleet',
        'depot_id' => 'ABC',
        'fleet_size' => 10,
        'variation_id' => 1,
    ];

    $this->variation = Variation::create([
        'client_id' => $this->payload['client_id'],
        'type' => $this->payload['type'],
        'subtype' => $this->payload['subtype'],
        'payload' => $this->payload,
        'status' => 'pending',
    ]);
});

test('handle - logs', function () {
    Log::shouldReceive('info')->once();

    $mock = $this->mock(ApiClient::class, function (MockInterface $mock) {
        $mock->allows('sendEvent')->andReturns(['success' => false]);
    });

    new DispatchEventJob($this->payload)->handle($mock);
});

test('handle - updates variation status on success', function () {
    $mock = $this->mock(ApiClient::class, function (MockInterface $mock) {
        $mock->expects('sendEvent')
            ->with($this->payload)
            ->andReturns([
                'success' => true,
                'data' => ['status' => 'approved'],
            ]);
    });

    new DispatchEventJob($this->payload)->handle($mock);

    expect(Variation::find($this->payload['variation_id'])->status)->toBe('approved');
});

test('handle - does not update variation on failure', function () {
    $mock = $this->mock(ApiClient::class, function (MockInterface $mock) {
        $mock->expects('sendEvent')
            ->with($this->payload)
            ->andReturns(['success' => false]);
    });

    new DispatchEventJob($this->payload)->handle($mock);

    expect(Variation::find($this->payload['variation_id'])->status)->toBe('pending');
});

test('getData - data is returned', function () {
    $job = new DispatchEventJob($this->payload);

    expect($job->getData())->toBe($this->payload);
});
