#!/usr/bin/php
<?php

// Not currently using
// include_once "/usr/local/task-daemon/vendor/autoload.php";

const SOURCE_DIRECTORY = __DIR__;
const RUN_DIRECTORY = '/run/task';
const CANCEL_DIRECTORY = RUN_DIRECTORY.'/cancel';
const CHANNELS_DIRECTORY = RUN_DIRECTORY.'/channels';
const COMMANDS_DIRECTORY = RUN_DIRECTORY.'/commands';
const CONFIG_DIRECTORY = RUN_DIRECTORY.'/config';
const FINISHED_DIRECTORY = RUN_DIRECTORY.'/finished';
const LINKS_DIRECTORY = RUN_DIRECTORY.'/links';
const QUEUE_DIRECTORY = RUN_DIRECTORY.'/queue';
const PID_FILE = RUN_DIRECTORY.'/task.pid';
const LOG_FILE = '/var/log/task.log';

// Check if process already exists
if (file_exists(PID_FILE)) {
    // Get PID
    $pid = trim(file_get_contents(PID_FILE));

    // Check if PID is actually running
    if (posix_kill($pid, 0)) {
        echo "Task daemon is already running: PID: $pid\n";

        return;
    }
}

declare(ticks=10);
pcntl_signal(SIGTERM, 'terminate'); // #15 - graceful shutdown
pcntl_signal(SIGHUP, 'terminate');  // #1 - hangup detected
pcntl_signal(SIGINT, 'terminate');  // #2 - from keyboard - Ctrl+C

// Set timezone
date_default_timezone_set('Europe/London');

function terminate($signo)
{
    // Debug
    // echo "terminate | " . PID_FILE . " | signal: $signo\n";

    stopChildren();

    // Log
    logService("daemon stopped | signal: $signo");

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

function createDirectory($directory)
{
    if (! file_exists($directory)) {
        mkdir($directory);
    }

    chown($directory, 'www-data');
    chgrp($directory, 'www-data');
}

function startChild($channel)
{
    $directory = CHANNELS_DIRECTORY."/$channel";
    $pidFile = RUN_DIRECTORY."/task.$channel.pid";
    $link = LINKS_DIRECTORY."/task.$channel.php";

    if (! file_exists($directory)) {
        // Debug
        // logService("creating directory: $directory");

        createDirectory($directory);
    }

    if (! file_exists($link)) {
        // Debug
        // logService("creating link: $link");

        symlink(SOURCE_DIRECTORY.'/task-child.php', $link);
    }

    if (file_exists($pidFile)) {
        // Debug
        // logService("child PID file is present: $pidFile");

        // Get PID
        $pid = trim(file_get_contents($pidFile));

        // Check if PID is actually running
        if (posix_kill($pid, 0)) {
            logService("child daemon is already running | channel: $channel | PID: $pid");

            return;
        }
    }

    logService("starting child daemon | channel: $channel");

    exec("/usr/bin/nohup $link > /dev/null 2>&1 &");
}

function assignTask($taskChannel, $taskFile)
{
    $sourceFile = QUEUE_DIRECTORY."/$taskFile";
    $targetFile = CHANNELS_DIRECTORY."/$taskChannel/".basename($taskFile);
    $processFile = COMMANDS_DIRECTORY."/process.$taskChannel";

    logService("assigning task: $taskFile | channel: $taskChannel");

    // Move task to relevant child daemon
    rename($sourceFile, $targetFile);

    // Debug
    // logService("notifying child daemon | channel: $taskChannel | file: $processFile");

    touch($processFile);
}

// Stop child services function
function stopChildren()
{
    // Log
    logService('stopping children');

    // NOTE: when running this daemon in the foreground, and pressing Ctrl-C from the terminal,
    // the child processes can be automatically terminated.
    // So we need to check if the child processes are still running.

    // Build array of child PIDs
    $pids = [];

    // Get PID files - excluding this daemon
    $pidFiles = array_diff(glob('/run/task/*.pid'), ['/run/task/task.pid']);

    // Get symlinks
    $symlinks = glob(LINKS_DIRECTORY.'/*.php');

    // Get channels
    $channels = glob(CHANNELS_DIRECTORY.'/*');

    // Process PID files
    foreach ($pidFiles as $pidFile) {
        $pid = trim(file_get_contents($pidFile));

        // Add to PIDs array
        $pids[$pid] = $pidFile;
    }

    // Wait for children to stop
    while (true) {
        // Init children PIDs running
        $childrenPids = false;

        // Loop PIDs
        foreach ($pids as $pid => $pidFile) {
            // Check if PID is running
            if (posix_kill($pid, 0)) {
                // Debug
                // logService("child PID is running: $pid");

                // Set children PIDs running
                $childrenPids = true;

                // Stop PID
                posix_kill($pid, SIGTERM);
            }
        }

        // Break now if all children PIDs have stopped
        if (! $childrenPids) {
            logService('all child daemons have terminated');

            // Cleanup child daemon PID files
            foreach ($pids as $pidFile) {
                if (file_exists($pidFile)) {
                    unlink($pidFile);
                }
            }

            break;
        }

        // Sleep 0.1 seconds
        usleep(100000);
    }

    // Remove channel directories
    foreach ($channels as $channel) {
        // Debug
        // logService("removing channel: $channel");

        exec("/usr/bin/rm -fr $channel");
    }

    // Remove symlinks
    foreach ($symlinks as $symlink) {
        // Debug
        // logService("removing symlink: $symlink");

        unlink($symlink);
    }
}

function getTasks()
{
    $tasks = array_diff(scandir(QUEUE_DIRECTORY, SCANDIR_SORT_ASCENDING), ['.', '..']);

    foreach ($tasks as $task) {
        processTask($task);
    }
}

function removeTasks()
{
    $tasks = array_diff(scandir(FINISHED_DIRECTORY, SCANDIR_SORT_ASCENDING), ['.', '..']);

    foreach ($tasks as $task) {
        // Get file date
        $taskFileAge = time() - filemtime(FINISHED_DIRECTORY."/$task");

        // Remove task after 1 hour
        if ($taskFileAge > 3600) {
            unlink(FINISHED_DIRECTORY."/$task");
        }
    }
}

function processTask($taskFile)
{
    // Get task
    $task = json_decode(file_get_contents(QUEUE_DIRECTORY."/$taskFile"), true);
    $taskChannel = $task['channel'];

    // Start child daemon
    startChild($taskChannel);

    // Assign the task to the relevant child daemon
    assignTask($taskChannel, $taskFile);
}

// Create directories
createDirectory(RUN_DIRECTORY);
createDirectory(CANCEL_DIRECTORY);
createDirectory(CHANNELS_DIRECTORY);
createDirectory(COMMANDS_DIRECTORY);
createDirectory(CONFIG_DIRECTORY);
createDirectory(FINISHED_DIRECTORY);
createDirectory(LINKS_DIRECTORY);
createDirectory(QUEUE_DIRECTORY);

// Drop pid into /run
file_put_contents(PID_FILE, getmypid()."\n");

// Log
logService('daemon started');

// Init time
$timeCurrent = time();

// Init loop counter
$loopCounter1 = 0;
$loopCounter2 = 0;

// Loop
while (true) {
    // Check for process now command
    if (file_exists(COMMANDS_DIRECTORY.'/process')) {
        unlink(COMMANDS_DIRECTORY.'/process');
        getTasks();
    }

    // Periodically check for tasks every 5 seconds
    if ($loopCounter1 == 50) {
        $loopCounter1 = 0;
        getTasks();
    }

    // Periodically remove old tasks every 1 hour
    if ($loopCounter2 == 18000) {
        $loopCounter2 = 0;
        removeTasks();
    }

    // Increment loop counters
    $loopCounter1++;
    $loopCounter2++;

    // Sleep for 0.1 seconds
    usleep(100000);
}
