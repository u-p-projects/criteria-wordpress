<?php
// Handles search form request in header

add_action('wp_ajax_search', 'searchRequest');
add_action('wp_ajax_nopriv_search', 'searchRequest');

function searchRequest() {
  $data = new SearchRequest($_POST);
  $data->init();
}

class SearchRequest {
  public function __construct($payload) {
    $this->query = $payload['query'];
    $this->siteUrl = get_bloginfo('url');
    $this->results = array();
    $this->response = array(
      'query' => $this->query,
      'results' => $this->getResults()
    );
  }

  public function init() {
    // Return payload
    $this->payload();
  }

  public function payload() {
    echo json_encode($this->response);
    die();
  }

  private function getResults() {
    $this->searchPosts();
    $this->searchTaxonomies();
    return $this->results ?: null;
  }

  private function searchTaxonomies() {
    if (preg_match('/[A-Za-z]/', $this->query)) {
      $queryList = array_filter(explode(' ', $this->query));
      $taxonomies = array(
        'designer' => 'Designer',
        'brand' => 'Brand',
        'product_category' => 'Category',
        'material' => 'Material',
        'colour' => 'Colour'
      );

      foreach ($taxonomies as $key => $taxonomy) {
        $terms = get_terms(array(
          'taxonomy' => $key,
          'hide_empty' => false,
        ));

        foreach ($terms as $term) {
          foreach ($queryList as $query) {
            if (preg_match("/$query/im", $term->name) && strlen($query) >= 3) {
              if ($key === 'product_category') {
                if ($term->parent === 0) $query = 'category';
                else $query = 'category=' . get_term($term->parent, 'product_category')->slug . '&subcategory';
              } else {
                $query = $key;
              }

              array_push($this->results, array(
                'title' => $term->name,
                'slug' => '/collection/filter?' . $query . '=' . $term->slug,
                'type' => $taxonomy
              ));
            }
          }
        }
      }
    }
  }

  private function searchPosts() {
    $posts = get_posts(array(
      'post_type' => array('collection', 'catalogue', 'edition'),
      's' => $this->query,
      'posts_per_page' => -1
    ));

    if ($posts) {
      foreach ($posts as $post) {
        array_push($this->results, array(
          'title' => $post->post_title,
          'slug' => explode($this->siteUrl, get_permalink($post->ID))[1],
          'type' => ucfirst($post->post_type)
        ));
      }
    }
  }
}
?>
