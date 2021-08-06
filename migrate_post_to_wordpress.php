<?php
require "./Configuration.php";
require "./WordPressImporter.php";

$config = getConfig();

// CONEXIÖN CON DB
function databaseConnect($params){
  try {
    return new PDO("mysql:host=localhost;dbname=".$params['DB_NAME'], $params['DB_USER'], $params['DB_PASS'],
                    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
  } catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
  }
}

// SE INSTANCIA IMPORTADOR y EXPORTADOR
$wordpress_importer = new WordPressImporter( databaseConnect( $config["DB_DESTINO"] ) );
$old_site_walker    = new OldSiteWalker( databaseConnect( $config["DB_ORIGEN"] ) );
