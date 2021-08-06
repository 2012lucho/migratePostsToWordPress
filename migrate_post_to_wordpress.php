<?php
require "./configuration.php";

$config = getConfig();
// CONEXIÖN CON DB
try {

  $connect = new PDO("mysql:host=localhost;dbname=".$config['DB']['DB_NAME'],$config['DB']['DB_USER'], $config['DB']['DB_PASS'],
  [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);

} catch (PDOException $e) {

  exit("Error: " . $e->getMessage());

}

//
