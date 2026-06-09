<?php

$baseDir = env('TASK_BASE_DIRECTORY', '/run/task');

return [
    'pid_file' => $baseDir.'/task.pid',
    'log_file' => '/var/log/task.log',

    'directory' => [
        'base' => $baseDir.'/run/task',
        'cancel' => $baseDir.'/cancel',
        'channels' => $baseDir.'/channels',
        'commands' => $baseDir.'/commands',
        'config' => $baseDir.'/config',
        'finished' => $baseDir.'/finished',
        'queue' => $baseDir.'/queue',
    ],
];
