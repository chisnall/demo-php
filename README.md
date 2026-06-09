# VOL Application

## Overview

The application consists of two separate APIs: a PHP Laravel API and a TypeScript Express API.

The PHP API is responsible for handling the variation request and communicating with the TypeScript API, which simulates the processing of the variation request.

## PHP API

The PHP API initially handles the variation request.

To access the API, you will need to obtain an API token by authenticating with your credentials.

### Architecture

Controller
->
Validator
->
Service
->
Worker
->
HTTP Client

**Controller**  
Receives the request, delegates validation, calls the appropriate service, and returns an HTTP response.

**Validator**  
Handles input sanitisation, type checking and other validation before any logic runs.

**Service**   
Contains the actual business logic. It handles the preparation of the event payload.

**HTTP Client**  
Dispatches the data to the external TypeScript API.

### Authentication
The application is protected with Laravel Sanctum, which provides a simple token-based authentication system.

Send a POST request to this endpoint:

`/api/login`

For example:

```
{
  "email": "test@dvsa.gov.uk",
  "password": "password"
}
```

This will return a JSON response containing your API token, which you can use to authenticate subsequent requests to the
API.

```
{
  "token": "1|DU7fLGcVt6FWjsfpVzqpZ3J0HYktH3GaIQzWjVAo361a1278"
}
```

The user must exist in the `users` table in the database.

To create a user, use Artisan Tinker:

```
php artisan tinker
```

Then run the following code in the Tinker console:

```
\App\Models\User::create([
    'name' => 'new',
    'email' => 'new@dvsa.gov.uk',
    'password' => \Illuminate\Support\Facades\Hash::make('password'),
]);
```

### Variation Endpoint
This endpoint allows you to submit a variation request for a fleet. 

Send a POST request to this endpoint:

`/api/variation/fleet`

Including the API token in the Authorisation header.

Example:

```
{
    "depot_id": 101,
    "fleet_size": 12
}
```

JSON response:

```
{
  "success": true,
  "message": "Variation received and is being processed.",
  "data": {
    "id": 27,
    "status": "pending",
    "status_url": "http://dvsa-php/api/variations/27/status"
  }
}
```

The `status_url` provided in the response has not been implemented and is simply an example of how you might check the
status of the variation request in a real application.

### Request Handling

The request is handled asynchronously.

What happens after the client submits the variation request:

1. The relevant controller (in this case, `FleetController`) receives the request and validates the input data.
2. The event is passed to the `VariationEventService` service. A database record is created for the variation request 
with a status of "pending".
3. A queued job is dispatched to process the variation request. This handles the request to the external TypeScript API.
A background worker is notified to process this job. Any other business logic should also be handled in this service.
4. The background worker processes the job, which involves sending a request to the external TypeScript API to handle 
the variation request. The response from the TypeScript API is used to update the status of the variation request in the 
database.
 
### Observing the Application

To observe the application, watch the logs of the Laravel application. 

You can do this by running the following command in your terminal:

```
tail -f storage/logs/laravel.log
```

## TypeScript API

This is a simple TypeScript application that simulates the processing of the variation request.

The Express.js framework is used to create a server that listens for incoming requests.

The API only returns responses. There is no actual business logic.

The variation status is randomly one of these values:
- approved
- rejected

To observe the application, watch the console.

## Tests

The PHP application is fully covered by tests.

To run tests:
```
export XDEBUG_MODE=coverage
php artisan test
```

The HTML based coverage report can be found in the `tests/Coverage` directory. 

Open the `index.html` file in a browser to view the report.
