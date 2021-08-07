<?php

class OldSiteWalker {
  protected $db_origen;
  protected $walker_config;
  protected $categories;
  protected $wordpress_importer;

  function __construct( $db_origen, $walker_config, $wordpress_importer ) {
    $this->db_origen          = $db_origen;
    $this->walker_config      = $walker_config;
    $this->wordpress_importer = $wordpress_importer;
  }

  public function walkPosts(){
    //Se hace consulta para la obtención de categorías
    $this->categories = $this->_getCategories();

    for( $c=0; $c < count($this->categories); $c++ ){
      $category = $this->categories[ $c ];
      print('> Procesando categoría: '.$category[ $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'] ].' id: '.$category['id']."\n");
      $this->wordpress_importer->insertCategory( [
        'id'   => $category['id'],
        'name' => $category[ $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'] ]
      ]);

      $category_posts = $this->_getPostsFromCategory( $category['id'] );
      print('  + Cantidad de posts: '.count($category_posts)."\n" );

      //Se recorren los posts
      for ( $d=0; $d < count($category_posts); $d++ ){
        $post = $category_posts[$d];

      }

      print("\n");
    }
  }

  protected function _getCategories(){
    $table_name = $this->walker_config['CATEGORY_TABLE']['TABLE_NAME'];
    $f_cat_name = $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'];
    $sql        = "SELECT id, $f_cat_name FROM `$table_name`";
    return $this->db_origen->query( $sql )->fetchAll();
  }

  protected function _getPostsFromCategory( $category_id ){
    $table_name   = $this->walker_config['POSTS_TABLE']['TABLE_NAME'];
    $cat_id_field = $this->walker_config['POSTS_TABLE']['CATEGORY_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$cat_id_field` = :category_id";
    $query        = $this->db_origen->prepare( $sql );
    $query->execute([':category_id'=> $category_id]);
    return $query->fetchAll(PDO::FETCH_OBJ);
  }
}
