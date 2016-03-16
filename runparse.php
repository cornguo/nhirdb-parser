#!/usr/bin/php -q
<?php
//shell_exec('rm -rf ./parse_log/*');
//shell_exec('rm -rf ./parsed/*');
//shell_exec('rm -rf ./log/*');

$dirs = scandir($argv[1]);

foreach ($dirs as $dir) {
    if ('.' === $dir[0]) {
        continue;
    }
    $command = 'find ' . trim($argv[1], '/') . '/' . $dir . ' -type f | sort';
    $files = explode("\n", trim(shell_exec($command)));
    $threads = 8;
    echo count($files) . " files found, will run {$threads} at a time.\n";
    foreach ($files as $n => $file) {
        $parseCommand = "./parse.php {$file}";
        echo "exec: $parseCommand\n";
        if (0 !== ($n + 1) % $threads) {
            exec("{$parseCommand} > /dev/null 2>&1 &");
        } else {
            exec("{$parseCommand} > /dev/null 2>&1");
        }
    }
}
