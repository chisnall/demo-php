<?php

namespace App\Http\Controllers;

use App\Http\Requests\FleetRequest;
use App\Services\VariationEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FleetController extends Controller
{
    public function __construct(
        private VariationEventService $eventService
    ) {}

    public function __invoke(FleetRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $payload = [
            'client_id' => Auth::user()->client_id,
            'type' => 'variation',
            'subtype' => 'fleet',
            'description' => 'Change fleet size',
            'depot_id' => $validatedData['depot_id'],
            'fleet_size' => $validatedData['fleet_size'],
        ];

        Log::info('FleetController | request received | client: '.$payload['client_id'].' | type: '.$payload['type'].' | subtype: '.$payload['subtype']);

        $result = $this->eventService->handleIncomingRequest($payload);

        return response()->json($result, Response::HTTP_ACCEPTED);
    }
}
