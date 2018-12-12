<?php
/**
 * Apply a custom filter to only show Projects which are not yet related to any Client (M2M relationship).
 * This filter is currently used on the Single Company page.
 * Toolset support reference: https://toolset.com/forums/topic/filter-a-view-with-no-relationship/
 */
add_filter('wpv_filter_query', 'ts_not_linked_to_client_filter', 10, 3);
function ts_not_linked_to_client_filter($query, $view_settings, $view_id) {
  $views = array( 64, 66 ); // only filter these views
  if( in_array( $view_id, $views ) ) {
    $project_ids = array(); // push all related project IDs here
    $client_args = array(
      'post_type' => 'client',
      'posts_per_page' => -1,
    );
    $clients = new WP_Query($client_args); // get all clients
    foreach ($clients->posts as $client ) {
      $projects = toolset_get_related_posts(
        $client->ID,
        'client-project',
        'parent',
        1000000,
        0,
        null,
        'post_id',
        'child'
      );
      $project_ids = array_merge($project_ids, $projects); // push the related project IDs into this array
    } 
    $query['post__not_in'] = isset( $query['post__not_in'] ) ? $query['post__not_in'] : array();
    $query['post__not_in'] = array_merge($query['post__not_in'], $project_ids ); // update the main query to exclude related project ids
  }
  return $query;
}