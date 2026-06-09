<?php

use App\Enums\TaskStatus;
use App\Services\TaskService;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Set up temp directories
    $this->queueDir = $this->tempStoragePath.'/task_queue_'.uniqid();
    $this->commandsDir = $this->tempStoragePath.'/task_commands_'.uniqid();

    mkdir($this->queueDir);
    mkdir($this->commandsDir);

    Config::set('task.directory.queue', $this->queueDir);
    Config::set('task.directory.commands', $this->commandsDir);
});

test('queue - returns ID and hash', function () {
    $result = app(TaskService::class)->queue('system', 'Test task', ['action' => 'resize']);

    expect($result)->toHaveKeys(['id', 'hash'])
        ->and($result['id'])->toBeInt()
        ->and($result['hash'])->toBeString()->toHaveLength(16);
});

test('queue - writes task file to queue directory', function () {
    $result = $result = app(TaskService::class)->queue('system', 'Test task', ['action' => 'resize']);

    $filename = $this->queueDir.'/'.$result['id'].'.'.$result['hash'];

    expect(file_exists($filename))->toBeTrue();
});

test('queue -  writes valid JSON to task file', function () {
    $result = $result = app(TaskService::class)->queue('system', 'Test task', ['action' => 'resize']);

    $filename = $this->queueDir.'/'.$result['id'].'.'.$result['hash'];

    $contents = json_decode(file_get_contents($filename), true);

    expect($contents)
        ->toBeArray()
        ->toHaveKeys(['id', 'hash', 'app_root', 'channel', 'description', 'started', 'finished', 'running', 'status', 'time_start', 'time_end', 'time_duration', 'task'])
        ->and($contents['channel'])->toBe('system')
        ->and($contents['description'])->toBe('Test task')
        ->and($contents['status'])->toBe(TaskStatus::PENDING->value);
});

test('queue - creates process command file', function () {
    $result = app(TaskService::class)->queue('system', 'Test task', ['action' => 'resize']);

    expect(file_exists($this->commandsDir.'/process'))->toBeTrue();
});
