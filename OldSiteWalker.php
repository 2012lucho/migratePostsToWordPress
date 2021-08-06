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

    for( $c=0; $c < count($this->categories); $c++ ){
      $category = $this->categories[ $c ];
      print('> Procesando categoría: '.$category[ $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'] ].' id: '.$category['id']."\n");

      $category_posts = $this->getPostsFromCategory( $category['id'] );
      print('  + Cantidad de posts: '.count($category_posts)."\n" );
      print("\n");
    }
  }

  public function getCategories(){
    $table_name = $this->walker_config['CATEGORY_TABLE']['TABLE_NAME'];
    $f_cat_name = $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'];
    $sql        = "SELECT id, $f_cat_name FROM `$table_name`";
    return $this->db_origen->query( $sql )->fetchAll();
  }

  public function getPostsFromCategory( $category_id ){
    $table_name   = $this->walker_config['POSTS_TABLE']['TABLE_NAME'];
    $cat_id_field = $this->walker_config['POSTS_TABLE']['CATEGORY_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$cat_id_field` = :category_id";
    $query        = $this->db_origen->prepare( $sql );
    $query->execute([':category_id'=> $category_id]);
    return $query->fetchAll(PDO::FETCH_OBJ);
  }
}
