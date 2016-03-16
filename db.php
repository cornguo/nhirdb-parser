<?php
// mysql
//$db = new PDO('mysql:host=localhost;dbname=nhirdb', 'user', 'pass', array(PDO::ATTR_PERSISTENT => true));
//$db->exec('SET NAMES UTF8');

// pgsql
$db = new PDO('pgsql:host=127.0.0.1 dbname=nhirdb user=user password=pass');
