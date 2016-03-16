#!/usr/bin/php -q
<?php
$dirs = scandir($argv[1]);

foreach ($dirs as $dir) {
    if ('.' === $dir[0]) {
        continue;
    }
    $path = trim($argv[1], '/') . '/' . $dir;
    $countCommand = "wc -l {$path}/*/*";
    echo "exec: $countCommand\n";
    exec("{$countCommand} > count/{$dir}.count 2>&1");
}
