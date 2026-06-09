<?php

namespace App\Jobs;

use App\Interfaces\ApiClient;
use App\Models\Variation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DispatchEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $payload
    ) {}

    public function handle(ApiClient $apiClient): void
    {
        Log::info('DispatchEventJob | job queued | client: '.$this->payload['client_id'].' | type: '.
            $this->payload['type'].' | subtype: '.$this->payload['subtype']);

        // TODO: used to demonstrate asynchronous processing
        //sleep(5);

        $response = $apiClient->sendEvent($this->payload);

        if ($response['success']) {
            Variation::where('id', $this->payload['variation_id'])
                ->update(['status' => $response['data']['status']]);
        }
    }

    public function getData(): array
    {
        return $this->payload;
    }
}
