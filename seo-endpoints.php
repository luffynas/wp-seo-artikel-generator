<?php
/**
 * Plugin Name: WP SEO Endpoints For Artikel Generator
 * Description: Adds custom REST API endpoints for Yoast SEO and Rank Math SEO.
 * Version: 1.2
 * Author: luffynas
 */

// Pastikan untuk tidak mengakses file ini secara langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include endpoint files
require_once plugin_dir_path( __FILE__ ) . 'endpoints/setup.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/yoast.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/rankmath.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/tags.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/categories.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/posts.php';
require_once plugin_dir_path( __FILE__ ) . 'endpoints/pages.php';

function add_categories_to_pages() {
    register_taxonomy_for_object_type('category', 'page');
}
add_action('init', 'add_categories_to_pages');

function add_tags_to_pages() {
    register_taxonomy_for_object_type('post_tag', 'page');
}
add_action('init', 'add_tags_to_pages');

// Hook untuk inisialisasi endpoint
add_action( 'rest_api_init', function () {
    register_rest_route( 'yoast/v1', '/save', array(
        'methods'  => 'POST',
        'callback' => 'yoast_save_seo_data',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'rankmath/v1', '/save', array(
        'methods'  => 'POST',
        'callback' => 'rankmath_save_seo_data',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route('tags/v1', '/add-tags', [
        'methods' => 'POST',
        'callback' => 'add_tags_to_post',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('categories/v1', '/fetch', [
        'methods' => 'GET',
        'callback' => 'get_all_categories',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('posts/v1', '/get-post-by-slug', array(
        'methods' => 'GET',
        'callback' => 'get_post_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            )
        ),
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));

    register_rest_route('posts/v1', '/update-post-by-slug', array(
        'methods' => 'POST',
        'callback' => 'update_post_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'title' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'content' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'featured_media' => array(
                'required' => false,
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
            'categories' => array(
                'required' => false,
                'validate_callback' => function($param, $request, $key) {
                    return is_array($param);
                }
            )
        ),
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));

    register_rest_route( 'pages/v1', '/get-page-by-slug', array(
        'methods' => 'GET',
        'callback' => 'get_page_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            )
        ),
    ));

    register_rest_route( 'pages/v1', '/update-page-by-slug', array(
        'methods' => 'POST',
        'callback' => 'update_page_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'tags' => array(
                'required' => false,
                'validate_callback' => function($param, $request, $key) {
                    return is_array($param);
                }
            ),
            'title' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'content' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            )
        ),
        'permission_callback' => function () {
            return current_user_can('edit_pages');
        }
    ));

    register_rest_route('pages/v1', '/update-page-tags-by-slug', array(
        'methods' => 'POST',
        'callback' => 'update_page_tags_by_slug',
        'args' => array(
            'slug' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param);
                }
            ),
            'tags' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_array($param);
                }
            )
        ),
        'permission_callback' => function () {
            return current_user_can('edit_pages');
        }
    ));

    // Setup Wordpress
    register_rest_route('setup/v1', '/install-template', array(
        'methods' => 'POST',
        'callback' => 'install_template_handler',
        'permission_callback' => function () {
            return current_user_can('install_themes');
        },
    ));

    register_rest_route('setup/v1', '/install-plugins', array(
        'methods' => 'POST',
        'callback' => 'install_plugins_handler',
        'permission_callback' => function () {
            return current_user_can('install_plugins');
        },
    ));

    register_rest_route('setup/v1', '/import-json', array(
        'methods' => 'POST',
        'callback' => 'handle_import_json',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ));

    register_rest_route('setup/v1', '/import-json-logo', array(
        'methods' => 'POST',
        'callback' => 'handle_import_json_logo',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
    ));
});
