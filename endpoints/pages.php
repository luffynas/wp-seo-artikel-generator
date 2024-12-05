<?php

// Mengambil Page berdasarkan slug
function get_page_by_slug( WP_REST_Request $request ) {
    $slug = $request['slug'];
    $pages = get_posts(array(
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
    ));

    if ( empty( $pages ) ) {
        return new WP_Error( 'no_page', 'No page found with that slug', array( 'status' => 404 ) );
    }

    return rest_ensure_response( $pages[0] );
}

// Mengupdate Page berdasarkan slug
function update_page_by_slug( WP_REST_Request $request ) {
    $slug = $request['slug'];
    $title = $request['title'];
    $content = $request['content'];
    $tags = $request['tags'];

    $pages = get_posts(array(
        'name' => $slug,
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
    ));

    if ( empty( $pages ) ) {
        return new WP_Error( 'no_page', 'No page found with that slug', array( 'status' => 404 ) );
    }

    $page_id = $pages[0]->ID;

    $updated_page = array(
        'ID'           => $page_id,
        'post_title'   => wp_strip_all_tags( $title ),
        'post_content' => $content,
    );

    wp_update_post( $updated_page );

    if (!empty($tags)) {
        wp_set_post_terms($page_id, $tags, 'post_tag');
    }

    return rest_ensure_response( $updated_page );
}