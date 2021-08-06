<?php

class WordPressImporter {
  protected $db_destino;
  protected $importer_config;

  function __construct( $DB_DESTINO, $importer_config ) {
    $this->db_destino      = $DB_DESTINO;
    $this->importer_config = $importer_config;
  }


  ///////////////////////////////////////////////////////////////////
  ///// TERMINOS Y CATEGORÏAS
  //////////////////////

  public function insertCategory( $category ){
    //Se inserta el término en la DB
    $term_id = $this->insertTerm([
      'name'       => $category['name'],
      'slug'       => $this->getSlug($category['name']),
      'term_group' => 0,
    ]);

    if ( $term_id > 0 ){
      print("  + Termino agregado a la DB, ID> ".$term_id."\n");
    } else {
      print("  E Termino no agregado a la DB"."\n");
      return 0;
    }

    $taxonomy_id = $this->insertTermTaxonomy(
      [
        'term_id'     => $term_id,
        'taxonomy'    => $this->importer_config['TERMS_TAXONOMY_TABLE']['TAXONOMY_CATEGORY'],
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
  public function insertPost( $post, $category_id ){
    $post['post_name'] = $this->getSlug($post['post_name']);

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
        ':post_name'             => $post['post_name'],
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

    if ($post_f > 0){
      print("   + Post procesado, ID> ".$post_f."\n");
      $relation_sh_id = $this->insertTermRelationship( [
        'object_id'        => $post_f,
        'term_taxonomy_id' => $category_id,
        'term_order'       => 0
      ]);
    } else {
      print("   A Post procesado, ID> ".$post_f."\n");
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

  public function insertImage(){

  }

  public function getSlug( $string ){
     $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
     return $slug;
  }
}
