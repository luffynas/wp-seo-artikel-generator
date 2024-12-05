<?php

// Fungsi untuk menyimpan data SEO Rank Math
function rankmath_save_seo_data( WP_REST_Request $request ) {
    $post_id = $request->get_param( 'post_id' );
    $seo_title = $request->get_param( 'seo_title' );
    $meta_description = $request->get_param( 'meta_description' );
    $focus_keyword = $request->get_param( 'focus_keyword' );
    $robots = $request->get_param( 'robots' );
    // $canonical_url = $request->get_param( 'canonical_url' );
    $schema_type = $request->get_param( 'schema_type' );
    $faq_schema = $request->get_param( 'faq_schema' );
    $facebook_title = $request->get_param( 'facebook_title' );
    $facebook_description = $request->get_param( 'facebook_description' );
    $facebook_image = $request->get_param( 'facebook_image' );
    $twitter_title = $request->get_param( 'twitter_title' );
    $twitter_description = $request->get_param( 'twitter_description' );
    $twitter_image = $request->get_param( 'twitter_image' );
    $twitter_card_type = $request->get_param( 'twitter_card_type' );

    // Update post meta dengan data Rank Math SEO
    update_post_meta( $post_id, 'rank_math_title', $seo_title );
    update_post_meta( $post_id, 'rank_math_description', $meta_description );
    update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_keyword );
    update_post_meta( $post_id, 'rank_math_robots', $robots );
    // update_post_meta( $post_id, 'rank_math_canonical_url', $canonical_url );
    update_post_meta( $post_id, 'rank_math_schema_type', $schema_type );
    update_post_meta( $post_id, 'rank_math_faq_schema', $faq_schema );
    update_post_meta( $post_id, 'rank_math_facebook_title', $facebook_title );
    update_post_meta( $post_id, 'rank_math_facebook_description', $facebook_description );
    update_post_meta( $post_id, 'rank_math_facebook_image', $facebook_image );
    update_post_meta( $post_id, 'rank_math_twitter_title', $twitter_title );
    update_post_meta( $post_id, 'rank_math_twitter_description', $twitter_description );
    update_post_meta( $post_id, 'rank_math_twitter_image', $twitter_image );
    update_post_meta( $post_id, 'rank_math_twitter_card_type', $twitter_card_type );

    // Memicu RankMath untuk melakukan penilaian ulang SEO
    do_action('rank_math/analytics/update_post_score', $post_id);

    return new WP_REST_Response( array(
        'status' => 'success',
        'message' => 'Rank Math SEO data saved successfully.',
    ), 200 );
}