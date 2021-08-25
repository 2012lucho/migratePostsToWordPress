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
      $category_inserted = $this->wordpress_importer->insertCategory( [
        'id'   => $category['id'],
        'name' => $category[ $this->walker_config['CATEGORY_TABLE']['CATEGORY_NAME_FIELD'] ]
      ]);

      $category_posts = $this->_getPostsFromCategory( $category['id'] );
      print('  + Cantidad de posts: '.count($category_posts)."\n" );

      //Se recorren los posts
      for ( $d=0; $d < count($category_posts); $d++ ){
        $post       = $category_posts[$d];
        $categories = [ $category_inserted ];

        //Verificación de embebido
        if ( $post->radiocut != Null || $post->radiocut != ''){
          //Se modifica la url para corregir la url del embebido
          $post->radiocut = str_replace( 'audiocut', 'audiocut/embed', $post->radiocut );

          //Se agrega el iframe al principio del post
          $post->contenido = '<iframe scrolling="no" src="'.$post->radiocut.'" width="100%" height="250px" frameborder="no"></iframe>'.$post->contenido;
          $categories[] = $this->wordpress_importer->insertCategory( [
            'id'   => '',  'name' => 'Audio'
          ]);
        }

        if ( strpos( $post->imagen, 'www.youtube.com' ) !== false  ){
          $post->contenido .= '<iframe width="820" height="360" src="'.$post->imagen.'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'.$post->contenido;
          $categories[] = $this->wordpress_importer->insertCategory( [
            'id'   => '',  'name' => 'Video'
          ]);
        }

        //Se continua con la carga del post
        $this->wordpress_importer->insertPost( [
            'post_author'           => 1,
            'post_date'             => $post->fecha,
            'post_date_gmt'         => $this->_getTimeGMT( $post->fecha ),
            'post_content'          => $post->contenido,
            'post_title'            => $post->titulo,
            'post_excerpt'          => $post->subtitulo,
            'post_status'           => 'publish',
            'comment_status'        => 'closed',
            'ping_status'           => 'closed',
            'post_password'         => '',
            'post_name'             => $post->titulo,
            'to_ping'               => '',
            'pinged'                => '',
            'post_modified'         => $post->fecha,
            'post_modified_gmt'     => $this->_getTimeGMT( $post->fecha ),
            'post_content_filtered' => '',
            'post_parent'           => 0,
            'guid'                  => '', //actualizar  post creacion del registro
            'menu_order'            => 0,
            'post_type'             => 'post',
            'post_mime_type'        => '',
            'comment_count'         => 0,
            'pie_imagen'            => $post->pie_imagen,
            'imagen'                => $post->imagen
        ], $categories, $this->_getTagsFromTagsString([ $post->keywords, $post->personas ]) );
      }

      print("\n");
    }
  }

  protected function _getTagsFromTagsString( $tagStrings ){
    $tags = [];
    for ( $c=0; $c < count($tagStrings); $c++ ){
      $tags = array_merge($tags, explode( $this->walker_config['KEYWORD_DIVIDER'], $tagStrings[$c]));
    }
    return array_unique($tags);
  }

  protected function _getTimeGMT( $dateTime ){
       $dateTime = new \DateTime($dateTime);
       $dateTime->modify('+ '.(-$this->walker_config['GMT_ZONE']).' hour');
       return $dateTime->format('Y-m-d H-i-s');
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
    $sql          = "SELECT * FROM `$table_name` WHERE `$cat_id_field` = :category_id AND eliminado = 0 AND habilitado = 1";
    $query        = $this->db_origen->prepare( $sql );
    $query->execute([':category_id'=> $category_id]);
    return $query->fetchAll(PDO::FETCH_OBJ);
  }
}
