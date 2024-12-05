<?php

// -- Menghapus canonical URL untuk Yoast SEO
// DELETE FROM wp_postmeta
// WHERE meta_key = '_yoast_wpseo_canonical';

// Fungsi untuk menyimpan data SEO Yoast
function yoast_save_seo_data( WP_REST_Request $request ) {
    $post_id = $request->get_param( 'post_id' );
    $seo_title = $request->get_param( 'seo_title' );
    $meta_description = $request->get_param( 'meta_description' );
    $focus_keyword = $request->get_param( 'focus_keyword' );
    $robots = $request->get_param( 'robots' );
    $schema_type = $request->get_param( 'schema_type' );
    $faq_schema = $request->get_param( 'faq_schema' );
    $facebook_title = $request->get_param( 'facebook_title' );
    $facebook_description = $request->get_param( 'facebook_description' );
    $facebook_image = $request->get_param( 'facebook_image' );
    $twitter_title = $request->get_param( 'twitter_title' );
    $twitter_description = $request->get_param( 'twitter_description' );
    $twitter_image = $request->get_param( 'twitter_image' );
    $twitter_card_type = $request->get_param( 'twitter_card_type' );

    // Update post meta dengan data Yoast SEO
    update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
    update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
    update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
    update_post_meta( $post_id, '_yoast_wpseo_robots', $robots );
    update_post_meta( $post_id, '_yoast_wpseo_schema_type', $schema_type );
    update_post_meta( $post_id, '_yoast_wpseo_faq_schema', $faq_schema );
    update_post_meta( $post_id, '_yoast_wpseo_facebook_title', $facebook_title );
    update_post_meta( $post_id, '_yoast_wpseo_facebook_description', $facebook_description );
    update_post_meta( $post_id, '_yoast_wpseo_facebook_image', $facebook_image );
    update_post_meta( $post_id, '_yoast_wpseo_twitter_title', $twitter_title );
    update_post_meta( $post_id, '_yoast_wpseo_twitter_description', $twitter_description );
    update_post_meta( $post_id, '_yoast_wpseo_twitter_image', $twitter_image );
    update_post_meta( $post_id, '_yoast_wpseo_twitter_card_type', $twitter_card_type );

    // Memicu Yoast untuk melakukan penilaian ulang SEO
    do_action('wpseo_hit', $post_id);

    return new WP_REST_Response( array(
        'status' => 'success',
        'message' => 'Yoast SEO data saved successfully.',
    ), 200 );
}