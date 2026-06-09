<?php

use App\Interfaces\ApiClient;
use App\Services\TypeScriptApiClient;

test('ApiClient interface resolves to TypeScriptApiClient', function () {
    Config::set('services.typescript_api.url', 'http://example.com');
    Config::set('services.typescript_api.token', 'test-token');

    $resolved = app(ApiClient::class);

    expect($resolved)->toBeInstanceOf(TypeScriptApiClient::class);
});
