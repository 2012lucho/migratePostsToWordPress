<?php

class OldSiteWalker {
  protected $db_origen;
  protected $walker_config;
  protected $categories;

  function __construct( $db_origen, $walker_config ) {
    $this->db_origen     = $db_origen;
    $this->walker_config = $walker_config;
  }

  public function walkPosts(){
    //Se hace consulta para la obtención de categorías
    $this->categories = $this->getCategories();
    var_dump($this->categories);
  }

  public function getCategories(){
    $table_name = $this->walker_config['CATEGORY_TABLE']['TABLE_NAME'];
    $f_cat_name = $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'];
    $sql        = "SELECT id, $f_cat_name FROM `$table_name`";
    return $this->db_origen->query( $sql )->fetchAll();
  }
}
