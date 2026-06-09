<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function loginAndGetToken(string $email, string $password): string
{
    $user = User::factory()->create([
        'email' => $email,
        'password' => bcrypt($password),
    ]);

    $response = test()->postJson('/api/login', [
        'email' => $email,
        'password' => $password,
    ]);

    return $response->json('token');
}

/*
|--------------------------------------------------------------------------
| Setup and tear-down
|--------------------------------------------------------------------------
| https://pestphp.com/docs/hooks
|
| This handles the temporary storage path.
|
*/

pest()->beforeEach(function () {
    // Save the system storage path
    $this->systemStoragePath = storage_path();

    // Set temporary storage path
    $this->tempStoragePath = '/run/pestStorage';

    // Create directory for temporary storage path
    if (! file_exists($this->tempStoragePath)) {
        mkdir($this->tempStoragePath);
    }

    // Set temporary storage path
    app()->useStoragePath($this->tempStoragePath);

    // Set log paths
    config([
        'logging.channels.single.path' => $this->tempStoragePath.'/logs/laravel.log',
        'logging.channels.daily.path' => $this->tempStoragePath.'/logs/laravel.log',
    ]);

    // Detect HTTP requests that have not been faked
    Http::preventStrayRequests();

    // Get /tmp files before
    $this->tmpFilesBefore = collect(File::files('/tmp'))->map->getPathname();
});

pest()->afterEach(function () {
    // Delete temporary storage path
    deleteDirectory($this->tempStoragePath);

    // Get /tmp files after
    $tmpFilesAfter = collect(File::files('/tmp'))->map->getPathname();

    // Determine new files in the /tmp directory
    $tmpFilesNew = $tmpFilesAfter->diff($this->tmpFilesBefore);

    // Delete new files
    foreach ($tmpFilesNew as $file) {
        unlink($file);
    }
});
