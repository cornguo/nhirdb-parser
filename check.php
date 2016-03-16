#!/usr/bin/php -q
<?php

// get path
$path = $argv[1];
echo "INPUT = {$path}\n";

// get pathinfo
$pathinfo = pathinfo($path);
$dirname = $pathinfo['dirname'];
$filename = $pathinfo['filename'];

// detect db name
$match = array();
preg_match('/\/[A-Z]+\/?/', $dirname, $match);
$parser = trim($match[0], '/');

// detect year
$match = array();
if (!preg_match('/\/[0-9]{4}\//', $dirname, $match)) {
    preg_match('/[0-9]{4}/', $filename, $match);
}

$year = intval($match[0]);

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

$fields = array();
$skips = array();
$dates = array();
$bools = array();

foreach ($parseRules as $n => $parseRule) {
    if (0 !== $n) {
        $exploded = explode(',', $parseRule);
        if (empty($exploded[1])) {
            $skips[$n-1] = 1;
        } else {
            if (false !== strstr($exploded[3], 'DATE')) {
                $dates[$n-1] = $exploded[3];
            }
            if ('BOOL' === $exploded[3]) {
                $bools[$n-1] = 1;
            }
            $fields[] = $exploded[1];
        }
    }
}

$skips[count($parseRules) - 1] = 1;

error_reporting(E_ALL);

$fields[] = "FILENAME";

$fp = fopen($path, 'rb');

require 'db.php';

$logFilename = "./check_log/{$parser}/{$year}/{$pathinfo['filename']}.log";
if (!is_dir("./check_log/{$parser}/{$year}")) {
    mkdir("./check_log/{$parser}/{$year}", 0775, true);
}

if (file_exists($logFilename)) {
    unlink($logFilename);
}

$cnt = 0;

$sql = '';
$fCnt = count($fields);

while ($row = fgetcsv($fp, null, "\t")) {
    $sql = "SELECT COUNT(PK) AS count FROM {$parser} WHERE\n";

    $values = array();
    foreach ($row as $n => $value) {
        if (isset($skips[$n])) {
            continue;
        }
        if (0 === strlen($value)) {
            $value = 'NULL';
        } else {
            if (isset($dates[$n])) {
                switch ($dates[$n]) {
                case 'DATE_TWY':
                    $value = intval($value) + 1911 . '-00-00';
                    break;
                case 'DATE_Y':
                    $value = substr($value, 0, 4) . '-00-00';
                    break;
                case 'DATE_YM':
                    $value = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-00';
                    break;
                case 'DATE_YMD':
                    $value = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
                    break;
                }
            }
            if (isset($bools[$n])) {
                if (1 === intval($value) || 'Y' === $value) {
                    $value = true;
                } else {
                    $value = false;
                }
            }
        }
        $values[] = $db->quote($value);
    }
    $values[] = $db->quote($filename);

    $vCnt = count($values);
    if ($fCnt !== $vCnt) {
        $errMsg = "ERROR: expected {$fCnt} columns but got {$vCnt}\nDATA:" . print_r($values, true) . "\n";
        file_put_contents($logFilename, $errMsg, FILE_APPEND);
    } else {
        $combine = array_combine($fields, $values);

        $tmpStmt = array();
        foreach ($combine as $f => $v) {
            $tmpStmt[] = "{$f} = {$v}";
        }
        $sql .= implode("\nAND ", $tmpStmt);
    }


    $sql .= ";\n";
    $sql = str_replace("= 'NULL'", "IS NULL", $sql);
    $sql = str_replace("(),\n", '', $sql);
    $ret = $db->query($sql);

    if (false === $ret) {
        $errMsg = "SQL error occured:\n" . print_r($db->errorInfo(), true) . "QUERY:\n{$sql}\n\n";
        file_put_contents($logFilename, $errMsg, FILE_APPEND);
    } else {
        if (1 === intval($ret->fetch()['count'])) {
            echo "LINE {$cnt} passed.\n";
        } else {
            $errMsg = "CHECK data not found:\nQUERY:\n{$sql}\n\n";
            file_put_contents($logFilename, $errMsg, FILE_APPEND);
        }
    }
    $sql = '';
    $cnt++;
}

$sql .= ";\n";
$sql = str_replace("= 'NULL'", "IS NULL", $sql);
$sql = str_replace("(),\n", '', $sql);
$ret = $db->query($sql);
if (false === $ret) {
    $errMsg = "SQL error occured:\n" . print_r($db->errorInfo(), true) . "QUERY:\n{$sql}\n\n";
    file_put_contents($logFilename, $errMsg, FILE_APPEND);
}

fclose($fp);

echo "DONE\n\n";
