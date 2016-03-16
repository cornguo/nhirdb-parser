#!/usr/bin/php -q
<?php

$configFiles = scandir('./mapping');

$columns = array();
$lenArr = array();

//echo "READ CONFIG:\n";
foreach ($configFiles as $configFile) {
    if ('.' === $configFile[0]) {
        continue;
    }

    $pathinfo = pathinfo($configFile);
    $indexes = array();

//    echo "- {$configFile}\n";
    $sql = "CREATE TABLE \"{$pathinfo['filename']}\" (\n";
//    $sql .= "PK INT(16) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,\n";
    $sql .= "FILENAME VARCHAR(30),\n";

    $configs = file("./mapping/{$configFile}", FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    $columns = array();

    foreach ($configs as $n => $config) {
        $exploded = explode(',', $config);

        $type = "VARCHAR";
        $length = intval($exploded[0]);

        if ('INT' === $exploded[2]) {
            $type = "FLOAT";
            $length = -1;
        } elseif ('BOOL' === $exploded[2]) {
            $type = "BOOLEAN";
            $length = -1;
        } elseif (false !== strstr($exploded[2], 'DATE')) {
            $type = "DATE";
            $indexes[] = $exploded[1];
            $length = -1;
        }

        if ('ID' === $exploded[1] || 'DRUG_NO' === $exploded[1]
            || false !== strstr($exploded[1], '_ID') || false !== strstr($exploded[1], 'ID_')
            || false !== strstr($exploded[1], 'ICD9') || false !== strstr($exploded[1], '_CODE')
            || false !== strstr($exploded[1], '_TYPE')) {
            $indexes[] = $exploded[1];
        }


        $colStr = "{$exploded[1]} {$type}";
        if (-1 !== $length) {
            $colStr .= "({$length})";
        }
//        $colStr .= " COMMENT '{$exploded[3]}'";
        $columns[] = $colStr;
    }
    $sql .= implode(",\n", $columns);

//    $sql .= "\n) ENGINE=InnoDB;\n";
    $sql .= "\n);\n";
    $sql .= "CREATE INDEX ON \"{$pathinfo['filename']}\" (FILENAME);\n";

    $indexes = array_unique($indexes);
    foreach ($indexes as $indexCol) {
        $sql .= "CREATE INDEX ON \"{$pathinfo['filename']}\" ({$indexCol});\n";
    }

    $sql .= "CREATE TRIGGER \"{$pathinfo['filename']}_insert_trigger\" BEFORE INSERT ON \"{$pathinfo['filename']}\" FOR EACH ROW EXECUTE PROCEDURE create_partition_and_insert();";
    echo $sql . "\n";
}
