<?php

namespace App\Interfaces;

interface ApiClient
{
    public function sendEvent(array $eventData): array;
}
