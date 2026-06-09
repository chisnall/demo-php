<?php

namespace App\Services;

use App\Jobs\DispatchEventJob;
use App\Models\Variation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VariationEventService
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function handleIncomingRequest(array $payload): array
    {
        Log::info('VariationEventService | event received | client: '.$payload['client_id'].' | type: '.
            $payload['type'].' | subtype: '.$payload['subtype']);

        $variation = Variation::create([
            'client_id' => Auth::user()->client_id,
            'type' => $payload['type'],
            'subtype' => $payload['subtype'],
            'payload' => $payload,
        ]);

        $payload['variation_id'] = $variation->id;

        DispatchEventJob::dispatch($payload);

        $this->runQueueWorker();

        return [
            'success' => true,
            'message' => 'Variation received and is being processed.',
            'data' => [
                'id' => $variation->id,
                'status' => 'pending',
                'status_url' => config('app.url').'/api/variations/'.$variation->id.'/status',
            ],
        ];
    }

    private function runQueueWorker(): void
    {
        $channel = 'queue';

        $description = 'Run queue';

        $task = [
            'type' => 'run-command',
            'command' => 'php '.base_path().'/artisan queue:work --stop-when-empty',
        ];

        $this->taskService->queue($channel, $description, $task);
    }
}
