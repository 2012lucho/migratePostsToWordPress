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
      "GMT_ZONE" => -3,
      "KEYWORD_DIVIDER" => ",",
    ],

    "WORDPRESS_IMPORTER" => [
      "TERMS_TABLE"      => [
        "TABLE_NAME"        => "wp_terms",
        "NAME_FIELD"        => "name"
      ],
      "THUMBNAILS" => [
        ['WIDTH' => 300,  'HEIGHT' => 198, 'NAME' => 'medium'],
        ['WIDTH' => 1024, 'HEIGHT' => 677, 'NAME' => 'large'],
        ['WIDTH' => 150,  'HEIGHT' => 150, 'NAME' => 'thumbnail'],
        ['WIDTH' => 768,  'HEIGHT' => 508, 'NAME' => 'medium_large'],
        ['WIDTH' => 420,  'HEIGHT' => 420, 'NAME' => 'gs-square-thumb'],
        ['WIDTH' => 420,  'HEIGHT' => 278, 'NAME' => 'gs-masonry-thumb'],
        ['WIDTH' => 392,  'HEIGHT' => 272, 'NAME' => 'colormag-highlighted-post'],
        ['WIDTH' => 390,  'HEIGHT' => 205, 'NAME' => 'colormag-featured-post-medium'],
        ['WIDTH' => 130,  'HEIGHT' => 90,  'NAME' => 'colormag-featured-post-small'],
        ['WIDTH' => 800,  'HEIGHT' => 445, 'NAME' => 'colormag-featured-image'],
        ['WIDTH' => 1155, 'HEIGHT' => 480, 'NAME' => 'colormag-elementor-block-extra-large-thumbnail'],
        ['WIDTH' => 600,  'HEIGHT' => 417, 'NAME' => 'colormag-elementor-grid-large-thumbnail'],
        ['WIDTH' => 285,  'HEIGHT' => 450, 'NAME' => 'colormag-elementor-grid-small-thumbnail'],
        ['WIDTH' => 575,  'HEIGHT' => 198, 'NAME' => 'colormag-elementor-grid-medium-large-thumbnail'],
        ['WIDTH' => 575,  'HEIGHT' => 198, 'NAME' => 'colormag-elementor-grid-medium-large-thumbnail'],
      ],
      "TERMS_TAXONOMY_TABLE"      => [
        "TABLE_NAME"        => "wp_term_taxonomy",
        "TAXONOMY_CATEGORY" => "category",
        "TAXONOMY_TAG"      => "post_tag",
        "TERM_ID_FIELD"     => "term_id",
        "ID_FIELD"          => "term_taxonomy_id"
      ],
      "POSTS_TABLE"      => [
        "TABLE_NAME"        => "wp_posts",
        "TITLE_FIELD"       => "post_title",
        "ID_FIELD"          => "ID",
        "POST_NAME_LENGTH"  => 200
      ],
      "TERM_RELATIONSHIP"      => [
        "TABLE_NAME"        => "wp_term_relationships",
        "POST_ID_FIELD"     => "object_id",
      ],
      "POST_META"      => [
        "TABLE_NAME"        => "wp_postmeta",
        "POST_ID_FIELD"     => "post_id"
      ],
      "SITE_URL" => "periferiaciencia.com.ar",
      "WP_CONTENT_DIR" => "./wp-content/uploads"
    ],

  ];
}
