<?php

class WordPressImporter {
  protected $db_destino;
  protected $importer_config;

  function __construct( $DB_DESTINO, $importer_config ) {
    $this->db_destino      = $DB_DESTINO;
    $this->importer_config = $importer_config;
  }


  ///////////////////////////////////////////////////////////////////
  ///// CATEGORÏAS Y TAGS
  //////////////////////

  public function insertCategory( $category ){
    $this->_insertTermAndTaxonmy( $category, "TAXONOMY_CATEGORY");
  }

  public function insertTag( $tag ){
    $this->_insertTermAndTaxonmy( $tag, "TAXONOMY_TAG");
  }

  protected function _insertTermAndTaxonmy( $category, $type ){
    //Se inserta el término en la DB
    $term_id = $this->insertTerm([
      'name'       => $category['name'],
      'slug'       => $this->getSlug($category['name']),
      'term_group' => 0,
    ]);

    if ( $term_id > 0 ){
      print("  + Termino agregado a la DB ".$type.", ID> ".$term_id."\n");
    } else {
      print("  E Termino no agregado a la DB"."\n");
      return 0;
    }

    $taxonomy_id = $this->insertTermTaxonomy(
      [
        'term_id'     => $term_id,
        'taxonomy'    => $this->importer_config['TERMS_TAXONOMY_TABLE'][ $type ],
        'description' => '',
        'parent'      => 0,
        'count'       => 0
      ]
    );
    if ( $taxonomy_id > 0 ){
      print("  + Taxonomía agregada a la DB, ID> ".$taxonomy_id."\n");
    } else {
      print("  E Taxonomía no agregada a la DB"."\n");
      return 0;
    }
    return $taxonomy_id;
  }

  public function insertTerm( $term ){
    //Se inserta el termino en caso de que ya no exusta en la base de datos
    $term_f = $this->getTermID( $term );
    if ( $term_f == 0 ){
      $table_name   = $this->importer_config['TERMS_TABLE']['TABLE_NAME'];
      $sql          = "INSERT INTO `$table_name` (name, slug, term_group) VALUES (:name, :slug, :term_group)";
      $query        = $this->db_destino->prepare( $sql );
      $query->execute([
        ':name'       => $term['name'],
        ':slug'       => $term['slug'],
        ':term_group' => $term['term_group']
      ]);
      return $this->db_destino->lastInsertId();
    } else {
      return $term_f;
    }
  }

  //INSERTA EL NUEVo TERMINO COMO CATEGORÍA
  public function insertTermTaxonomy( $term ){
    $taxonomy_f = $this->getTaxonomyIDByTermId( $term['term_id'] );
    if ( $taxonomy_f == 0 ){
      $table_name   = $this->importer_config['TERMS_TAXONOMY_TABLE']['TABLE_NAME'];
      $sql          = "INSERT INTO `$table_name` (term_id, taxonomy, description, parent,  count) VALUES (:term_id, :taxonomy, :description, :parent,  :count)";
      $query        = $this->db_destino->prepare( $sql );
      $query->execute([
        ':term_id'     => $term['term_id'],
        ':taxonomy'    => $term['taxonomy'],
        ':description' => $term['description'],
        ':parent'      => $term['parent'],
        ':count'       => $term['count'],
      ]);
      return $this->db_destino->lastInsertId();
    } else {
      return $taxonomy_f;
    }
  }

  public function insertTermRelationship( $termRelationSh ){
    $term_r_f = $this->getTermRelationshipByPostId( $termRelationSh['object_id'] );
    if ( $term_r_f == 0 ){
      $table_name   = $this->importer_config['TERM_RELATIONSHIP']['TABLE_NAME'];
      $sql          = "INSERT INTO `$table_name` (object_id, term_taxonomy_id, term_order) VALUES (:object_id, :term_taxonomy_id, :term_order)";
      $query        = $this->db_destino->prepare( $sql );
      $query->execute([
        ':object_id'        => $termRelationSh['object_id'],
        ':term_taxonomy_id' => $termRelationSh['term_taxonomy_id'],
        ':term_order'       => $termRelationSh['term_order'],
      ]);
      return $this->db_destino->lastInsertId();
    } else {
      return $term_r_f;
    }
  }

  public function getTermRelationshipByPostId( $post_id ){
    $table_name     = $this->importer_config['TERM_RELATIONSHIP']['TABLE_NAME'];
    $post_id_field  = $this->importer_config['TERM_RELATIONSHIP']['POST_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$post_id_field` = :post_id";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':post_id'=> $post_id]);
    $query = $query->fetchAll(PDO::FETCH_OBJ);

    if ( count($query) > 0){
      $query = get_object_vars($query[0]);
      return $query[ $this->importer_config['TERM_RELATIONSHIP']['POST_ID_FIELD'] ];
    } else {
      return 0;
    }
  }

  public function getTermID( $term ){
    $table_name     = $this->importer_config['TERMS_TABLE']['TABLE_NAME'];
    $ter_name_field = $this->importer_config['TERMS_TABLE']['NAME_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$ter_name_field` = :term_name";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':term_name'=> $term[$ter_name_field]]);
    $query = $query->fetchAll(PDO::FETCH_OBJ);

    if ( count($query) > 0){
      $query = $query[0];
      return $query->term_id;
    } else {
      return 0;
    }
  }

  public function getTaxonomyIDByTermId( $term_id ){
    $table_name     = $this->importer_config['TERMS_TAXONOMY_TABLE']['TABLE_NAME'];
    $term_id_field  = $this->importer_config['TERMS_TAXONOMY_TABLE']['TERM_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$term_id_field` = :term_id";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':term_id'=> $term_id]);
    $query = $query->fetchAll(PDO::FETCH_OBJ);

    if ( count($query) > 0){
      $query = get_object_vars($query[0]);
      return $query[ $this->importer_config['TERMS_TAXONOMY_TABLE']['ID_FIELD'] ];
    } else {
      return 0;
    }
  }

  ///////////////////////////////////////////////////////////////////
  ///// POSTS
  //////////////////////
  public function insertPost( $post, $category_id, $tags ){
    $post['post_name'] = $this->getSlug($post['post_name']);

    $post_f = $this->insertPostElement( $post );

    if ($post_f > 0){
      //Se asocian los posts con sus respectivas categorías
      print("   + Post procesado (asociado con su categoría), ID> ".$post_f."\n");
      $relation_sh_id = $this->insertTermRelationship( [
        'object_id'        => $post_f,
        'term_taxonomy_id' => $category_id,
        'term_order'       => 0
      ]);

      //Se asocian los posts con sus respectivos tags
      for ( $k = 0; $k < count($tags); $k++ ){
        $tag = $tags[$k];
        $tag_id = $this->insertTag([
          'id'   => $category_id,
          'name' => $tag
        ]);

        $relation_sh_id = $this->insertTermRelationship( [
          'object_id'        => $post_f,
          'term_taxonomy_id' => $tag_id,
          'term_order'       => 0
        ]);
        print("    + Etiqueta encontrada, procesando > ".$tag." ID > ".$tag_id."\n");

        //Se asocian las imágenes con el post
        $post_image_id = $this->insertImage([
          'post_author'           => $post['post_author'],
          'post_date'             => $post['post_date'],
          'post_date_gmt'         => $post['post_date_gmt'],
          'post_content'          => '',
          'post_title'            => $this->getSlug($post['pie_imagen']),
          'post_excerpt'          => $post['pie_imagen'],
          'post_status'           => 'inherit',
          'comment_status'        => 'close',
          'ping_status'           => 'close',
          'post_password'         => $post['post_password'],
          'post_name'             => $this->getSlug($post['pie_imagen']),
          'to_ping'               => '',
          'pinged'                => '',
          'post_modified'         => $post['post_modified'],
          'post_modified_gmt'     => $post['post_modified_gmt'],
          'post_content_filtered' => '',
          'post_parent'           => $post_f,
          'guid'                  => $post['guid'], //actualizar  post creacion del registro
          'menu_order'            => 0,
          'post_type'             => 'attachment',
          'post_mime_type'        => 'image/jpeg',
          'comment_count'         => 0,
          'imagen'                => $post['imagen'],
        ], $post_f);

        if ($post_image_id != 0){
          $this->insertPostMeta([
            'post_id'    => $post_f,
            'meta_key'   => '_thumbnail_id',
            'meta_value' => $post_image_id,
          ]);
        }
      }
    } else {
      print("   A Post procesado, ID> ".$post_f."\n");
    }

    return $post_f;
  }

  protected function insertPostElement( $post ){
    $post_f = $this->getPostIdByTitle( $post['post_title'] );
    if ( $post_f == 0 ){
      $table_name   = $this->importer_config['POSTS_TABLE']['TABLE_NAME'];
      $sql          = "INSERT INTO `$table_name` (post_author, post_date, post_date_gmt, post_content,  post_title, post_excerpt, post_status, comment_status, ping_status, post_password,
               post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count)
                        VALUES (:post_author, :post_date, :post_date_gmt, :post_content,  :post_title, :post_excerpt, :post_status, :comment_status, :ping_status, :post_password,
                                 :post_name, :to_ping, :pinged, :post_modified, :post_modified_gmt, :post_content_filtered, :post_parent, :guid, :menu_order, :post_type, :post_mime_type, :comment_count)";
      $query        = $this->db_destino->prepare( $sql );
      $query->execute([
        ':post_author'           => $post['post_author'],
        ':post_date'             => $post['post_date'],
        ':post_date_gmt'         => $post['post_date_gmt'],
        ':post_content'          => $post['post_content'],
        ':post_title'            => $post['post_title'],
        ':post_excerpt'          => $post['post_excerpt'],
        ':post_status'           => $post['post_status'],
        ':comment_status'        => $post['comment_status'],
        ':ping_status'           => $post['ping_status'],
        ':post_password'         => $post['post_password'],
        ':post_name'             => truncate( $post['post_name'],  $this->importer_config['POSTS_TABLE']['POST_NAME_LENGTH']),
        ':to_ping'               => $post['to_ping'],
        ':pinged'                => $post['pinged'],
        ':post_modified'         => $post['post_modified'],
        ':post_modified_gmt'     => $post['post_modified_gmt'],
        ':post_content_filtered' => $post['post_content_filtered'],
        ':post_parent'           => $post['post_parent'],
        ':guid'                  => $post['guid'], //actualizar  post creacion del registro
        ':menu_order'            => $post['menu_order'],
        ':post_type'             => $post['post_type'],
        ':post_mime_type'        => $post['post_mime_type'],
        ':comment_count'         => $post['comment_count'],
      ]);
      $post_f = $this->db_destino->lastInsertId();
    }
    return $post_f;
  }

  public function getPostIdByTitle( $title ){
    $table_name   = $this->importer_config['POSTS_TABLE']['TABLE_NAME'];
    $title_field  = $this->importer_config['POSTS_TABLE']['TITLE_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$title_field` = :title";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':title'=> $title]);
    $query = $query->fetchAll(PDO::FETCH_OBJ);

    if ( count($query) > 0){
      $query = get_object_vars($query[0]);
      return $query[ $this->importer_config['POSTS_TABLE']['ID_FIELD'] ];
    } else {
      return 0;
    }
  }

  ///////////////////////////////////////////////////////////////////
  ///// IMÄGENES Y METADATA
  //////////////////////

  public function insertImage( $image_data, $post_id ){
    print("   D Descargando imagen de POST, ID> ".$image_data['imagen']."\n");

    if ( strpos( $image_data['imagen'], $this->importer_config['SITE_URL'] ) === false ){
      print("   A El recurso no se pertenece al sitio ID> ".$image_data['imagen']."\n");
    } else {
      //se inserta la imagen como un nuevo post
      $post_image_id = $this->insertPostElement( $image_data );

      //Creación de estructura de directorios de acuerdo a la fecha
      $strTime = strtotime($image_data['post_date']);
      $rutaCarpeta = $this->importer_config['WP_CONTENT_DIR']."/".date( "Y", $strTime )."/".date( "m", $strTime );

      //Se comprueba si la imagen existe para no descargarla de nuevo
      $imgExplode = explode("/", $image_data['imagen']);
      $fileName   = array_pop($imgExplode);
      $rutaImg    = $rutaCarpeta.'/'.$fileName;

      if ( file_exists($rutaImg) ){
        print("   = El recurso ya fue descargado! > ".$fileName."\n");
        return $post_image_id;
      } else {

        //se hace peticion curl para obtener la imagen
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $image_data['imagen']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION,3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $curlDatos = curl_exec ($ch);
        curl_close ($ch);

        if (!file_exists($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0777, true);
        }

        //Se verifica que la ruta se corresponda a una imagen
        $extension_explode = explode( ".", $fileName );
        $extension_explode = array_pop($extension_explode);
        if ($curlDatos !== false && ($extension_explode == 'gif' || $extension_explode == 'JPG' || $extension_explode == 'jpeg' || $extension_explode == 'jpg' || $extension_explode == 'png' || $extension_explode == 'webp') ){
          $miarchivo  = fopen($rutaImg, "w+");

          // Insertamos en la carpeta la imagen
          fputs($miarchivo, $curlDatos);
          fclose($miarchivo);

          return $post_image_id;
        } else {
          print("   Err El recurso no se trata de una imagen! o no hay conexion a internet ?¿> ".$fileName."\n");
        }

      }

    }
    return 0;
  }

  public function insertPostMeta( $postMeta ){
    $post_meta_id = $this->getPostMetaByPostId( $postMeta['post_id'] );
    if ( $post_meta_id == 0 ){
      print("   + Se agregó registro post_meta "."\n");
      $table_name   = $this->importer_config['POST_META']['TABLE_NAME'];
      $sql          = "INSERT INTO `$table_name` (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)";
      $query        = $this->db_destino->prepare( $sql );
      $query->execute([
        ':post_id'    => $postMeta['post_id'],
        ':meta_key'   => $postMeta['meta_key'],
        ':meta_value' => $postMeta['meta_value'],
      ]);
      return $this->db_destino->lastInsertId();
    } else {
      print("   = registro post_meta existente"."\n");
      return $post_meta_id;
    }
  }

  public function getPostMetaByPostId( $post_id ){
    $table_name     = $this->importer_config['POST_META']['TABLE_NAME'];
    $post_id_field  = $this->importer_config['POST_META']['POST_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$post_id_field` = :post_id AND meta_key='_thumbnail_id'";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':post_id'=> $post_id]);
    $query = $query->fetchAll(PDO::FETCH_OBJ);

    if ( count($query) > 0){
      $query = get_object_vars($query[0]);
      return $query[ $this->importer_config['POST_META']['POST_ID_FIELD'] ];
    } else {
      return 0;
    }
  }

  ///////////////////////////////////////////////////////////////////
  ///// GRAL
  //////////////////////
  public function getSlug( $string ){
     $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
     return $slug;
  }

  protected function truncate($text, $chars = 120) {
    if(strlen($text) > $chars) {
        $text = $text.' ';
        $text = substr($text, 0, $chars);
        $text = substr($text, 0, strrpos($text ,' '));
        $text = $text.'...';
    }
    return $text;
}
}
