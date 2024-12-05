<?php

// Fungsi untuk menyimpan data Tags
function add_tags_to_post($request) {
    $post_id = $request->get_param('post_id');
    $tags = $request->get_param('tags');

    if (!$post_id || !$tags) {
        return new WP_Error('missing_data', 'Missing post_id or tags', ['status' => 400]);
    }

    wp_set_post_tags($post_id, $tags, true);

    return new WP_REST_Response('Tags added successfully.', 200);
}

function update_page_tags_by_slug( WP_REST_Request $request ) {
    $slug = $request['slug'];
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

    $tag_ids = array();
    foreach ($tags as $tag_name) {
        $tag = get_term_by('name', $tag_name, 'post_tag');
        if ($tag) {
            $tag_ids[] = $tag->term_id;
        } else {
            $new_tag = wp_insert_term($tag_name, 'post_tag');
            if (!is_wp_error($new_tag)) {
                $tag_ids[] = $new_tag['term_id'];
            }
        }
    }

    wp_set_post_tags($page_id, $tag_ids, false);

    return rest_ensure_response(array(
        'id' => $page_id,
        'slug' => $slug,
        'tags' => $tags
    ));
}