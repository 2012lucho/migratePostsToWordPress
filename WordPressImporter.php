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
    return $this->_insertTermAndTaxonmy( $category, "TAXONOMY_CATEGORY");
  }

  public function insertTag( $tag ){
    return $this->_insertTermAndTaxonmy( $tag, "TAXONOMY_TAG");
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
    $term_r_f = $this->getTermRelationshipByPostIdAndTaxonomyID( $termRelationSh['object_id'], $termRelationSh['term_taxonomy_id'] );
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

  public function getTermRelationshipByPostIdAndTaxonomyID( $post_id, $taxonomy_id ){
    $table_name     = $this->importer_config['TERM_RELATIONSHIP']['TABLE_NAME'];
    $post_id_field  = $this->importer_config['TERM_RELATIONSHIP']['POST_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$post_id_field` = :post_id AND term_taxonomy_id = :taxonomy_id";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':post_id'=> $post_id, ':taxonomy_id' => $taxonomy_id]);
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
          'name' => $tag
        ]);

        $relation_sh_id = $this->insertTermRelationship( [
          'object_id'        => $post_f,
          'term_taxonomy_id' => $tag_id,
          'term_order'       => 0
        ]);
        print("    + Etiqueta encontrada, procesando > ".$tag." ID > ".$tag_id.' Relationship ID > '.$relation_sh_id."\n");

      }

      //Se asocian las imágenes con el post
      $post_image_info = $this->insertImage([
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
        'post_mime_type'        => '', //mas adelante se define de acuerdo al archivo descargado
        'comment_count'         => 0,
        'imagen'                => $post['imagen'],
      ], $post_f);

      if ($post_image_info['post_id'] != 0){
        $this->insertPostMeta([
          'post_id'    => $post_f,
          'meta_key'   => '_thumbnail_id',
          'meta_value' => $post_image_info['post_id'],
        ]);

        $this->insertPostMeta([
          'post_id'    => $post_image_info['post_id'],
          'meta_key'   => '_wp_attached_file',
          'meta_value' => $this->getInternalUrlFromPath( $post_image_info['path'] ),
        ]);

        //se generan las miniaturas
        $thumbnails = [];
        for ( $c=0; $c < count($this->importer_config['THUMBNAILS']); $c++ ){
          print("   Generando miniatura ".$this->importer_config['THUMBNAILS'][$c]['NAME']."\n");
          $thumbnails[ $c ]['width']  = $this->importer_config['THUMBNAILS'][$c]['WIDTH'];
          $thumbnails[ $c ]['height'] = $this->importer_config['THUMBNAILS'][$c]['HEIGHT'];
          $thumbnails[ $c ]['name']   = $this->importer_config['THUMBNAILS'][$c]['NAME'];
          $thumbnails[ $c ]['img']    = $this->newResizedImage(
            $post_image_info['name'],
            $post_image_info['path'],
            $thumbnails[ $c ]['width'],
            $thumbnails[ $c ]['height']
          );

          //las miniaturas se guardaran en formato jpg en todos los casos para hacer las cosas de forma mas practica
          if ($thumbnails[ $c ]['img'] != Null){
            $thumbnails[ $c ]['file'] = $this->replaceFileExt( $thumbnails[ $c ]['name'].'_'.$post_image_info['name'], 'jpg');
            imagejpeg($thumbnails[ $c ]['img'], $post_image_info['directory'].'/'.$thumbnails[ $c ]['file']);
            $thumbnails[ $c ]['mime-type'] = 'image/jpeg';
          }
        }

        //Metadata de imagenes
        $this->insertPostMeta([
          'post_id'    => $post_image_info['post_id'],
          'meta_key'   => '_wp_attachment_metadata',
          'meta_value' => $this->getLastImageWpAttachmentMetaData( $post_image_info['path'], $thumbnails ),
        ]);
      }

    } else {
      print("   A Post procesado, ID> ".$post_f."\n");
    }

    return $post_f;
  }

  protected function replaceFileExt( $fileName, $ext ){
    $nameExplode = explode( '.', $fileName );
    $len         = count($nameExplode);
    if ( $len == 0){ //si llega string vacio
      return '';
    }

    if ( $len == 1 ){ // si el archivo no tiene extension
      return $filename.'.'.$ext;
    }

    if ( $len > 1){ // en caso que el archivo tenga extension
      $nameExplode[ $len - 1 ] = $ext;
      return implode( '.', $nameExplode );
    }

  }

  protected function newResizedImage($imgName, $imgPath, $xmax, $ymax){
        $ext = explode(".", $imgName);
        $ext = $ext[count($ext)-1];

        $imagen = Null;
        if($ext == "jpg" || $ext == 'JPG' || $ext == "jpe" || $ext == "jpeg")
            $imagen = imagecreatefromjpeg($imgPath);
        elseif($ext == "png")
            $imagen = imagecreatefrompng($imgPath);
        elseif($ext == "gif")
            $imagen = imagecreatefromgif($imgPath);

        if ($imagen == Null){
          return Null;
        }

        $x = imagesx($imagen);
        $y = imagesy($imagen);

        if($x <= $xmax && $y <= $ymax){
            return $imagen;
        }

        if($x >= $y) {
            $nuevax = $xmax;
            $nuevay = $nuevax * $y / $x;
        }
        else {
            $nuevay = $ymax;
            $nuevax = $x / $y * $nuevay;
        }

        $img2 = imagecreatetruecolor($nuevax, $nuevay);
        imagecopyresized($img2, $imagen, 0, 0, 0, 0, floor($nuevax), floor($nuevay), $x, $y);
        return $img2;
  }

  protected function getLastImageWpAttachmentMetaData( $path, $thumbnails ){
    $imageData = [
      'width'  => '',
      'height' => '',
      'file'   => '',
      'sizes'  => [],
      'image_meta' => [
        'aperture'          => '0', //en el serializado está como string
        'credit'            => '',
        'camera'            => '',
        'caption'           => '',
        'created_timestamp' => '0',
        'copyright'         => '',
        'focal_length'      => '0',
        'iso'               => '0',
        'shutter_speed'     => '0',
        'title'             => '',
        'orientation'       => '0',
        'keywords'          => []
      ]
    ];

    if ( file_exists( $path ) ){
      $imgSize = getimagesize($path);
      $imageData["width"]  = $imgSize[0];
      $imageData["height"] = $imgSize[1];
      $imageData["file"]   = $this->getInternalUrlFromPath( $path );

      for ( $c=0; $c < count($thumbnails); $c++ ){
        $imageData["sizes"][ $thumbnails[$c]['name'] ] = [
          'file'      => $thumbnails[ $c ]['file'],
          'width'     => $thumbnails[ $c ]['width'],
          'height'    => $thumbnails[ $c ]['height'],
          'mime-type' => $thumbnails[ $c ]['mime-type'],
        ];
      }
    }

    return serialize( $imageData );
  }

  protected function getInternalUrlFromPath( $path ){
    return str_replace( $this->importer_config['WP_CONTENT_DIR']."/", '', $path );
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
        ':post_name'             => $this->truncate( $post['post_name'],  $this->importer_config['POSTS_TABLE']['POST_NAME_LENGTH']),
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

  protected function getImageNameFURL( $url ){
    $imgExplode = explode( "/", $url );
    return array_pop($imgExplode);
  }

  public function insertImage( $image_data, $post_id ){
    $imgInfo = [
      'name'      => '',
      'path'      => '',
      'directory' => '',
      'post_id'   => '',
      'mime_type' => ''
    ];
    print("   D Descargando imagen de POST, ID> ".$image_data['imagen']."\n");

    if ( strpos( $image_data['imagen'], $this->importer_config['SITE_URL'] ) === false ){
      print("   A El recurso no se pertenece al sitio ID> ".$image_data['imagen']."\n");
    } else {
      //Creación de estructura de directorios de acuerdo a la fecha
      $strTime = strtotime($image_data['post_date']);
      $imgInfo['directory'] = $this->importer_config['WP_CONTENT_DIR']."/".date( "Y", $strTime )."/".date( "m", $strTime );

      //Se comprueba si la imagen existe para no descargarla de nuevo
      $imgInfo['name'] = $this->getImageNameFURL( $image_data['imagen'] );

      $imgInfo['path'] = $imgInfo['directory'].'/'.$imgInfo['name'];

      if ( file_exists($imgInfo['path']) ){
        print("   = El recurso ya fue descargado! > ".$imgInfo['name']."\n");
        $imgInfo['post_id'] = $this->getPostIdByTitle( $image_data['post_title'] );
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

        if (!file_exists($imgInfo['directory'])) {
            mkdir($imgInfo['directory'], 0777, true);
        }

        //Se verifica que la ruta se corresponda a una imagen
        $extension_explode = explode( ".", $imgInfo['name'] );
        $extension_explode = array_pop($extension_explode);
        if ($curlDatos !== false && ($extension_explode == 'gif' || $extension_explode == 'JPG' || $extension_explode == 'jpe' || $extension_explode == 'jpeg' || $extension_explode == 'jpg' || $extension_explode == 'png' || $extension_explode == 'webp') ){
          $miarchivo  = fopen($imgInfo['path'], "w+");

          // Insertamos en la carpeta la imagen
          fputs($miarchivo, $curlDatos);
          fclose($miarchivo);
        } else {
          print("   Err El recurso no se trata de una imagen! o no hay conexion a internet ?¿> ".$imgInfo['name']."\n");
        }
      }

      //se inserta la imagen como un nuevo post
      $imgInfo['mime_type']         = image_type_to_mime_type(exif_imagetype($imgInfo['path']) );
      $image_data['post_mime_type'] = $imgInfo['mime_type'];
      $imgInfo['post_id']           = $this->insertPostElement( $image_data );
    }
    return $imgInfo;
  }

  public function insertPostMeta( $postMeta ){
    $post_meta_id = $this->getPostMetaByPostIdAKey( $postMeta['post_id'], $postMeta['meta_key'] );
    if ( $post_meta_id == 0 && $postMeta['post_id'] != ''){
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

  public function getPostMetaByPostIdAKey( $post_id, $meta_key ){
    $table_name     = $this->importer_config['POST_META']['TABLE_NAME'];
    $post_id_field  = $this->importer_config['POST_META']['POST_ID_FIELD'];
    $sql          = "SELECT * FROM `$table_name` WHERE `$post_id_field` = :post_id AND meta_key=:meta_key";
    $query        = $this->db_destino->prepare( $sql );
    $query->execute([':post_id'=> $post_id, ':meta_key' => $meta_key]);
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
