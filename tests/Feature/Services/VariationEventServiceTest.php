<?php

use App\Jobs\DispatchEventJob;
use App\Models\User;
use App\Services\VariationEventService;
use App\Services\TaskService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->mock(TaskService::class)->allows('queue');

    $this->user = User::factory()->create([
        'id' => 1,
        'client_id' => 5001,
    ]);

    $this->validatedData = [
        'client_id' => 5001,
        'type' => 'variation',
        'subtype' => 'fleet',
        'depot_id' => 'ABC',
        'fleet_size' => 10,
    ];
});

test('handleIncomingRequest - dispatches DispatchEventJob with correct payload', function () {
    $this->actingAs($this->user);

    app(VariationEventService::class)->handleIncomingRequest($this->validatedData);

    Queue::assertPushed(DispatchEventJob::class, function ($job) {
        $payload = $job->getData();

        return $payload['client_id'] === $this->validatedData['client_id']
            && $payload['type'] === $this->validatedData['type']
            && $payload['subtype'] === $this->validatedData['subtype']
            && $payload['depot_id'] === $this->validatedData['depot_id']
            && $payload['fleet_size'] === $this->validatedData['fleet_size'];
    });
});

test('handleIncomingRequest - returns expected response structure', function () {
    $this->actingAs($this->user);

    $result = app(VariationEventService::class)->handleIncomingRequest($this->validatedData);

    expect($result)
        ->toHaveKeys(['success', 'message', 'data'])
        ->and($result['success'])->toBeTrue()
        ->and($result['data'])->toHaveKeys(['id', 'status', 'status_url']);
});

test('handleIncomingRequest - queues task via TaskService', function () {
    $this->actingAs($this->user);

    $mock = $this->mock(TaskService::class);

    $mock->expects('queue')
        ->with('queue', 'Run queue', Mockery::on(fn ($task) => $task['type'] === 'run-command' &&
            str_contains($task['command'], 'queue:work --stop-when-empty')
        ));

    app(VariationEventService::class)->handleIncomingRequest($this->validatedData);
});
