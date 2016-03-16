#!/usr/bin/php -q
<?php

$configFiles = scandir('./conf');

$columns = array();
$comments = array();
$lenArr = array();

echo "READ CONFIG:\n";
foreach ($configFiles as $configFile) {
    if ('.' === $configFile[0]) {
        continue;
    }

    echo "- {$configFile}\n";

    $prefix = substr($configFile, 0, strpos($configFile, '.'));

    if (!isset($columns[$prefix])) {
        $columns[$prefix] = array();
        $comments[$prefix] = array();
    }

    $configs = file("./conf/{$configFile}", FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    $columnKeys = array();

    foreach ($configs as $n => $config) {
        $exploded = explode(',', $config);
        if (0 === $n) {
            $key = $exploded[0];
            $len = intval($exploded[1]);
        } else {
            if (empty($exploded[1])) {
                continue;
            }
//            $key = "{$exploded[1]},{$exploded[2]},{$exploded[3]}";
            $key = "{$exploded[1]},{$exploded[3]}";
            $columnKeys[] = $key;

            if (!isset($comments[$prefix][$key])) {
                $comments[$prefix][$key] = array();
            }
            $comments[$prefix][$key][] = $exploded[2];

            $len = "{$exploded[0]}";
        }
        if (!isset($lenArr[$key]) || $len > $lenArr[$key]) {
            $lenArr[$key] = $len;
        }
    }

    $columns[$prefix][] = $columnKeys;
}

$columnsUniq = array();

echo "MERGING CONFIG:\n";
foreach ($columns as $configName => $configs) {
    $columnsUniq[$configName] = array();

    echo "- {$configName}\n";

    foreach ($configs as $config) {
        $columnsUniq[$configName] = array_merge($columnsUniq[$configName], $config);
    }

    $columnsUniq[$configName] = array_unique($columnsUniq[$configName]);
}

echo "WRITE MAPPING:\n";
foreach ($columnsUniq as $configName => $configs) {
    $filename = "./mapping/{$configName}.colmap";
    $mapping = '';

    echo "- {$configName}\n";

    foreach ($configs as $config) {
        $comment = implode(';', array_unique($comments[$configName][$config]));
        $mapping .= "{$lenArr[$config]},{$config},{$comment}\n";
    }

    file_put_contents($filename, trim($mapping));
}
