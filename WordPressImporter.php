<?php

class WordPressImporter{
  private $db_destino;

  function __construct( $DB_DESTINO ) {
    $this->db_destino = $DB_DESTINO;
  }
}
