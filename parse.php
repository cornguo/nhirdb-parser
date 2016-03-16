#!/usr/bin/php -q
<?php

// get path
$path = $argv[1];
echo "INPUT = {$path}\n";

// get pathinfo
$pathinfo = pathinfo($path);
$dirname = $pathinfo['dirname'] . '/';
$filename = $pathinfo['filename'];

// detect db name
$match = array();
preg_match('/\/[A-Z]+\/?/', $dirname, $match);
$parser = trim($match[0], '/');

// detect year
$match = array();
if (!preg_match('/\/([0-9]{4})\//', $dirname, $match)) {
    preg_match('/([0-9]{4})/', $filename, $match);
}
$year = intval(trim($match[1], '/'));

echo "YEAR = {$year}, DATA NAME = {$parser}\n";

// find suitable parser
if (file_exists("./conf/{$parser}.conf")) {
    // no years specified
    $configFilename = "./conf/{$parser}.conf";
} else {
    // find with year range
    $configs = scandir("./conf");
    $possible = array();

    foreach ($configs as $config) {
        if (false !== strstr($config, $parser)) {
            $exploded = explode('.', $config);
            if ($exploded[0] !== $parser) {
                continue;
            }
            $yearRange = explode('-', $exploded[1]);
            if (empty($yearRange[0])) {
                $yearRange[0] = 0;
            }
            if (empty($yearRange[1])) {
                $yearRange[1] = 9999;
            }
            if ($year >= $yearRange[0] && $year <= $yearRange[1]) {
                $configFilename = "./conf/{$config}";
                break;
            }
        }
    }
}

echo "CONFIG = {$configFilename}\n";

$parseRules = file($configFilename, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

$outputFilename = "./parsed/{$parser}/{$year}/{$pathinfo['filename']}.csv";

if (!is_dir("./parsed/{$parser}/{$year}")) {
    mkdir("./parsed/{$parser}/{$year}", 0775, true);
}

echo "OUTPUT = {$outputFilename}\n";

$logFilename = "./parse_log/{$parser}/{$year}/{$pathinfo['filename']}.log";
if (!is_dir("./parse_log/{$parser}/{$year}")) {
    mkdir("./parse_log/{$parser}/{$year}", 0775, true);
}

if (file_exists($logFilename)) {
    unlink($logFilename);
}

echo "ERROR LOG = {$logFilename}\n";

error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    restore_error_handler();
    throw new Exception($errstr);
}, E_ALL);

// start parsing
$pattern = "";
$fields = array();
$lineLen = null;
$checkLen = 1;

foreach ($parseRules as $n => $parseRule) {
    if (0 === $n) {
        $lineLen = intval(substr($parseRule, strpos($parseRule, ',') + 1));
    } else {
        $exploded = explode(',', $parseRule);
        $pattern .= "(?P<{$exploded[1]}>.{{$exploded[0]}})";
        $fields[] = $exploded[1];
        $checkLen += intval($exploded[0]);
    }
}

if ($checkLen !== $lineLen) {
    file_put_contents($logFilename, "config file length not match, expected [{$lineLen}] but got [{$checkLen}]\n", FILE_APPEND);
    exit;
}

$rfp = fopen($path, 'r');
$wfp = fopen($outputFilename, 'w');
$lineCnt = 0;

while ($line = fgets($rfp)) {
    $lineCnt++;
    $lineLen = strlen($line);

    if ($lineLen !== $lineLen) {
        file_put_contents($logFilename, "line [{$lineCnt}]: length not match, expected [{$lineLen}] but got [{$lineLen}]\n", FILE_APPEND);
        continue;
    }

    $match = array();
    $matched = preg_match('/' . $pattern . '/', $line, $match);
    if (false !== $matched) {
        try {
            foreach ($fields as $field) {
                fputs($wfp, iconv('big5', 'utf-8//IGNORE', trim($match[$field])) . "\t");
            }
            fputs($wfp, "\n");
        } catch (Exception $e) {
            file_put_contents($logFilename, "line [{$lineCnt}]: " . $e->getMessage() . "\n", FILE_APPEND);
            fputs($wfp, "\n");
        }
    } else {
        file_put_contents($logFilename, "line [{$lineCnt}]: pattern not matched\n", FILE_APPEND);
    }
}

fclose($rfp);
fclose($wfp);

echo "DONE\n\n";
