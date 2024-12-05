<?php

function get_post_by_slug($request) {
    // Ambil parameter dari request
    $slug = $request['slug'];

    // Query untuk mendapatkan ID postingan berdasarkan slug
    $post = get_page_by_path($slug, OBJECT, 'post');

    if ($post) {
        // Ambil informasi postingan
        $post_data = array(
            'ID'           => $post->ID,
            'title'        => $post->post_title,
            'content'      => $post->post_content,
            'featured_media' => get_post_thumbnail_id($post->ID),
            'categories'   => wp_get_post_categories($post->ID)
        );

        return new WP_REST_Response($post_data, 200);
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}

function update_post_by_slug($request) {
    // Ambil parameter dari request
    $slug = $request['slug'];
    $title = $request['title'];
    $content = $request['content'];
    $featured_media = $request['featured_media'];
    $categories = isset($request['categories']) ? $request['categories'] : [];

    // Query untuk mendapatkan ID postingan berdasarkan slug
    $post = get_page_by_path($slug, OBJECT, 'post');

    if ($post) {
        // Update post
        $post_id = $post->ID;
        $post_data = array(
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish'
        );

        $updated_post_id = wp_update_post($post_data);

        // Update featured media
        if (!is_wp_error($updated_post_id) && !empty($featured_media)) {
            set_post_thumbnail($updated_post_id, $featured_media);
        }

        // Update categories
        if (!is_wp_error($updated_post_id) && !empty($categories)) {
            wp_set_post_terms($updated_post_id, $categories, 'category');
        }

        if (!is_wp_error($updated_post_id)) {
            return new WP_REST_Response(array('message' => 'Post updated successfully', 'id' => $post->ID), 200);
        } else {
            return new WP_Error('post_update_failed', 'Failed to update post', array('status' => 500));
        }
    } else {
        return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
    }
}