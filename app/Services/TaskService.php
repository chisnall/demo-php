<?php

namespace App\Services;

use App\Enums\TaskStatus;

class TaskService
{
    public function queue(string $channel, string $description, array $task): array
    {
        // Generate task ID
        $taskId = (int) (microtime(true) * 1000000);

        // Generate hash
        $taskHash = \Str::random(16);

        // Set output array
        $taskOutput['id'] = $taskId;
        $taskOutput['hash'] = $taskHash;
        $taskOutput['app_root'] = base_path();
        $taskOutput['channel'] = $channel;
        $taskOutput['description'] = $description;
        $taskOutput['started'] = false;
        $taskOutput['finished'] = false;
        $taskOutput['running'] = false;
        $taskOutput['status'] = TaskStatus::PENDING->value;
        $taskOutput['time_start'] = null;
        $taskOutput['time_end'] = null;
        $taskOutput['time_duration'] = 0;
        $taskOutput['task'] = $task;
        $taskOutput['task']['output'] = null;

        // Encode to JSON
        $taskJson = json_encode($taskOutput);

        // Set filename
        $filename = config('task.directory.queue').'/'.$taskId.'.'.$taskHash;

        // Write task to file
        file_put_contents($filename, $taskJson);

        // Issue command to the task daemon
        touch(config('task.directory.commands').'/process');

        // Return
        return [
            'id' => $taskId,
            'hash' => $taskHash,
        ];
    }
}
