<?php

function getConfig(){
  return [

    "DB_ORIGEN" => [
      "DB_NAME" => "",
      "DB_USER" => "",
      "DB_PASS" => "",
    ],

    "DB_DESTINO" => [
      "DB_NAME" => "",
      "DB_USER" => "",
      "DB_PASS" => "",
    ],

    "WALKER" => [
      "POSTS_TABLE"      => [
        "TABLE_NAME"        => "noticias",
        "POSTS_TAGS_FIELD"  => "keywords",
        "CATEGORY_ID_FIELD" => "categorias",
      ],
      "CATEGORY_TABLE"   => [
        "TABLE_NAME"          => "categorias",
        "CATEGORY_NAME_FIELD" => "categoria"
      ],
    ],

    "WORDPRESS_IMPORTER" => [
      "TERMS_TABLE"      => [
        "TABLE_NAME"        => "wp_terms",
        "NAME_FIELD"        => "name"
      ],
      "TERMS_TAXONOMY_TABLE"      => [
        "TABLE_NAME"        => "wp_term_taxonomy",
        "TAXONOMY_CATEGORY" => "category",
        "TERM_ID_FIELD"     => "term_id",
        "ID_FIELD"          => "term_taxonomy_id"
      ],
    ],

  ];
}
