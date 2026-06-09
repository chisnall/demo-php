#!/usr/bin/php
<?php

// Not currently using
// include_once "/usr/local/task-daemon/vendor/autoload.php";

// Get script name
$scriptName = $_SERVER['SCRIPT_NAME'];

// Set timezone
date_default_timezone_set('Europe/London');

// Determine channel
$channel = explode('.', $scriptName)[1];

// Init last task time
$lastTaskTime = time();

// Setup constants
define('CHANNEL', $channel);
const RUN_DIRECTORY = '/run/task';
const CANCEL_DIRECTORY = RUN_DIRECTORY.'/cancel';
const COMMANDS_DIRECTORY = RUN_DIRECTORY.'/commands';
const FINISHED_DIRECTORY = RUN_DIRECTORY.'/finished';
const QUEUE_DIRECTORY = RUN_DIRECTORY.'/channels/'.CHANNEL;
const PID_FILE = RUN_DIRECTORY.'/task.'.CHANNEL.'.pid';
const LOG_FILE = '/var/log/task.log';

// Check if process already exists
if (file_exists(PID_FILE)) {
    // Get PID
    $pid = trim(file_get_contents(PID_FILE));

    // Check if PID is actually running
    if (posix_kill($pid, 0)) {
        echo 'Child daemon '.CHANNEL." is already running | PID: $pid\n";

        return;
    }
}

declare(ticks=10);
pcntl_signal(SIGTERM, 'terminate'); // #15 - graceful shutdown
pcntl_signal(SIGHUP, 'terminate');  // #1 - hangup detected
pcntl_signal(SIGINT, 'terminate');  // #2 - from keyboard - Ctrl+C

function terminate($signo)
{
    // Log
    logService('child daemon stopped | channel: '.CHANNEL." | signal: $signo");

    // Remove PID file
    if (file_exists(PID_FILE)) {
        unlink(PID_FILE);
    }

    // Exit
    exit(0);
}

// Log daemon function
function logService($entry)
{
    $fullEntry = date('Y-m-d H:i:s')."   $entry\n";
    file_put_contents(LOG_FILE, $fullEntry, FILE_APPEND);
}

// Check uptime function
function checkIdle()
{
    global $lastTaskTime;

    $idleTime = time() - $lastTaskTime;

    // Terminate if idle for more than 10 minutes
    if ($idleTime > 600) {
        logService('child daemon idle | channel: '.CHANNEL." | idle time: $idleTime seconds");

        terminate(15);
    }
}

// Get files in a directory function
function getFiles($directory, $sort = SCANDIR_SORT_ASCENDING): array
{
    return array_values(array_diff(scandir($directory, $sort), ['.', '..']));
}

// Get tasks function
function getTasks()
{
    $tasks = getFiles(QUEUE_DIRECTORY);

    foreach ($tasks as $task) {
        processTask($task);
    }
}

// Process task function
function processTask($taskFile)
{
    global $lastTaskTime;

    // Update last task time
    $lastTaskTime = time();

    // Get full path to task file
    $sourceFile = QUEUE_DIRECTORY.'/'.basename($taskFile);

    // Set target file once finished
    $targetFile = FINISHED_DIRECTORY.'/'.basename($taskFile);

    // Set cancel request file
    $cancelFile = CANCEL_DIRECTORY.'/'.basename($taskFile);

    // Get task
    $task = json_decode(file_get_contents($sourceFile), true);
    $taskId = $task['id'];

    // Update task
    $task['started'] = true;

    // Write task file
    file_put_contents($sourceFile, json_encode($task, JSON_UNESCAPED_SLASHES));

    // Check if cancellation has been requested
    if (file_exists($cancelFile)) {
        // Update task
        $task['finished'] = true;
        $task['status'] = 'cancelled';

        // Write task file
        file_put_contents($sourceFile, json_encode($task, JSON_UNESCAPED_SLASHES));

        // Move task to finished
        rename($sourceFile, $targetFile);

        // Set file ownership - it can change to root for some reason
        chown($targetFile, 'www-data');
        chgrp($targetFile, 'www-data');

        // Log
        $logEntry = CHANNEL." | $taskId | type: ".$task['task']['type'].' | cancelled';
        logService($logEntry);

        return;
    }

    // Init
    $output = null;
    $logExtra = null;

    // Start time
    $taskStartTime = microtime(true);

    // Update task
    $task['running'] = true;
    $task['status'] = 'running';
    $task['time_start'] = round(microtime(true), 2);

    // Write task file
    file_put_contents($sourceFile, json_encode($task, JSON_UNESCAPED_SLASHES));

    // Run method
    if ($task['task']['type'] == 'run-method') {
        $taskAppRoot = $task['app_root'];
        $taskClass = $task['task']['class'];
        $taskMethod = $task['task']['method'];
        $taskParams = $task['task']['params'] ?? []; // optional

        // Debug
        logService("run-method | root: $taskAppRoot | class: $taskClass | method: $taskMethod");

        // Extra for log
        $logExtra = ' | class: '.$taskClass.' | method: '.$taskMethod;
        if ($taskParams) {
            $logExtra .= ' | params: '.implode(' ', $taskParams);
        }

        // Run Artisan command
        $output = shell_exec("php $taskAppRoot/artisan app:run-method '$taskClass' $taskMethod ".implode(' ', $taskParams));
    }

    // Run command
    if ($task['task']['type'] == 'run-command') {
        $taskCommand = $task['task']['command'];

        // Log friendly command
        $taskCommandLog = preg_replace('#/usr/bin/php.*artisan#', 'artisan', $taskCommand);

        // Extra for log
        $logExtra = ' | command: '.$taskCommandLog;

        // Run command
        $output = shell_exec($taskCommand);
    }

    // End time
    $taskEndTime = microtime(true);
    $taskTotalTime = ($taskEndTime - $taskStartTime);
    $taskTotalTime = round($taskTotalTime, 2);

    // Check output
    if ($output) {
        // Decode JSON so the JSON is not encoded again below
        $outputJson = json_decode(trim($output), true);

        // Check for no error
        if (json_last_error() === JSON_ERROR_NONE) {
            $output = $outputJson;
        }
    }

    // Update task
    $task['running'] = false;
    $task['finished'] = true;
    $task['time_end'] = null;
    $task['time_duration'] = $taskTotalTime;
    $task['task']['output'] = $output;
    $task['status'] = 'complete';

    // Write task file
    file_put_contents($sourceFile, json_encode($task, JSON_UNESCAPED_SLASHES));

    // Move task to finished
    rename($sourceFile, $targetFile);

    // Set file ownership - it can change to root for some reason
    chown($targetFile, 'www-data');
    chgrp($targetFile, 'www-data');

    // Log
    $logEntry = CHANNEL." | $taskId | type: ".$task['task']['type'].$logExtra.' | time: '.number_format($taskTotalTime, 2).' seconds';
    logService($logEntry);
}

// Drop pid into /run
file_put_contents(PID_FILE, getmypid()."\n");

// Log
logService('child daemon started | channel: '.CHANNEL);

// Init loop counter
$loopCounter = 0;

// Loop
while (true) {
    // Check for process now command
    if (file_exists(COMMANDS_DIRECTORY.'/process.'.CHANNEL)) {
        // Debug
        // logService("process now command received | channel: " . CHANNEL);

        unlink(COMMANDS_DIRECTORY.'/process.'.CHANNEL);

        getTasks();
    }

    // Periodically check every 5 seconds
    if ($loopCounter == 50) {
        // Debug
        // logService("periodically check for tasks | channel: " . CHANNEL);

        $loopCounter = 0;

        getTasks();

        checkIdle();
    }

    // Increment loop counter
    $loopCounter++;

    // Sleep for 0.1 seconds
    usleep(100000);
}
