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
      return false;
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
      return false;
    }
    return true;
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

  public function insertTermRelationship( $term, $post){

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

  public function insertPost( $post ){

  }

  public function insertImage(){

  }

  public function getSlug( $string ){
     $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
     return $slug;
  }
}
