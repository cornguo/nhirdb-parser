#!/usr/bin/php -q
<?php
$dirs = scandir($argv[1]);

foreach ($dirs as $dir) {
    if ('.' === $dir[0]) {
        continue;
    }
    $command = 'find ' . trim($argv[1], '/') . '/' . $dir . ' -type f | sort';
    $files = explode("\n", trim(shell_exec($command)));
    echo count($files) . " files found.\n";
    foreach ($files as $n => $file) {
        $importCommand = "./import.php {$file}";
        echo "exec: $importCommand\n";
        //exec("{$importCommand} > /dev/null 2>&1");
        system($importCommand);
    }
}
