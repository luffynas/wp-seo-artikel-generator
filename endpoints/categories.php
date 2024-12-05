<?php

function get_all_categories() {
    $categories = get_categories(array(
        'hide_empty' => false,
    ));

    $formatted_categories = array_map(function($category) {
        return array(
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
        );
    }, $categories);

    return new WP_REST_Response($formatted_categories, 200);
}