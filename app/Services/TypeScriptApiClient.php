<?php

namespace App\Services;

use App\Interfaces\ApiClient;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TypeScriptApiClient implements ApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $apiAccessKey,
        private string $apiSecretKey,
    ) {}

    public function sendEvent(array $eventData): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-API-Access-Key' => $this->apiAccessKey,
                'X-API-Secret-Key' => $this->apiSecretKey,
            ])
                ->timeout(5)
                ->post("{$this->baseUrl}/variation", $eventData);

            if (! $response->successful()) {
                Log::error('TypeScript API response | '.$response->status().' | '.json_encode($response->json()));

                return [
                    'success' => false,
                    'message' => 'API error '.$response->status(),
                ];
            }
        } catch (Exception $exception) {
            // @codeCoverageIgnoreStart
            if (app()->environment('testing') && str_contains($exception->getMessage(), 'without a matching fake')) {
                fwrite(STDERR, "\e[1;31m🛑 STRAY API ERROR DETECTED:\e[0m\n");
                fwrite(STDERR, "\e[33m".$exception->getMessage()."\e[0m\n\n");
                throw $exception;
            }
            // @codeCoverageIgnoreEnd

            Log::critical('TypeScript API response | '.$exception->getMessage());

            return [
                'success' => false,
                'message' => 'API error - unable to contact API.',
            ];
        }

        // Log::info('TypeScript API response | '.$response->status().' | '.json_encode($response->json()));
        Log::info('TypeScript API response | '.$response->status().' | message: '.$response->json('message').' | status: '.$response->json('data.status'));

        return [
            'success' => true,
            'message' => 'API request successful.',
            'data' => $response->json()['data'],
        ];
    }
}
