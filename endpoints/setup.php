<?php

function install_template_handler(WP_REST_Request $request) {
    $template_url = $request->get_param('url');
    error_log('install tempalte :: ' . $template_url);

    if (empty($template_url)) {
        return new WP_Error('missing_url', 'Template URL is required', array('status' => 400));
    }

    // Inisialisasi WordPress Filesystem API
    global $wp_filesystem;

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    $creds = request_filesystem_credentials('', '', false, false, null);

    if (!WP_Filesystem($creds)) {
        return new WP_Error('filesystem_error', 'Could not initialize filesystem.', array('status' => 500));
    }

    try {
        // Unduh file template
        $temp_file = download_url($template_url);
        if (is_wp_error($temp_file)) {
            throw new Exception('Gagal mengunduh file template');
        }

        // Ekstrak file template ke folder tema WordPress
        $theme_dir = get_theme_root();
        if (!$wp_filesystem->is_dir($theme_dir)) {
            $wp_filesystem->mkdir($theme_dir);
        }

        $unzip_result = unzip_file($temp_file, $theme_dir);
        if (is_wp_error($unzip_result)) {
            throw new Exception('Gagal mengekstrak file template: ' . $unzip_result->get_error_message());
        }

        // Hapus file sementara
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }

        // Aktifkan tema
        $theme_slug = basename($template_url, '.zip');
        switch_theme($theme_slug);

        return rest_ensure_response(['success' => true, 'message' => 'Template berhasil diinstal dan diaktifkan.']);
    } catch (Exception $e) {
        return new WP_Error('install_failed', $e->getMessage(), array('status' => 500));
    } finally {
        if (!empty($temp_file) && file_exists($temp_file)) {
            unlink($temp_file);
        }
    }
}

function install_plugins_handler(WP_REST_Request $request) {
    $plugin_urls = $request->get_param('urls');

    if (empty($plugin_urls) || !is_array($plugin_urls)) {
        return new WP_Error('missing_urls', 'Daftar URL plugin diperlukan.', array('status' => 400));
    }

    global $wp_filesystem;

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/template.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $creds = request_filesystem_credentials('', '', false, false, null);

    if (!WP_Filesystem($creds)) {
        return new WP_Error('filesystem_error', 'Could not initialize filesystem.', array('status' => 500));
    }

    $results = [];

    foreach ($plugin_urls as $plugin_url) {
        try {
            // Unduh file plugin
            $temp_file = download_url($plugin_url);
            if (is_wp_error($temp_file)) {
                throw new Exception('Gagal mengunduh file plugin: ' . $plugin_url);
            }

            // Ekstrak file plugin ke folder plugin WordPress
            $plugin_dir = WP_PLUGIN_DIR;
            $unzip_result = unzip_file($temp_file, $plugin_dir);
            if (is_wp_error($unzip_result)) {
                throw new Exception('Gagal mengekstrak file plugin: ' . $plugin_url);
            }

            // Aktifkan plugin
            // $plugin_slug = basename($plugin_url, '.zip') . '/' . basename($plugin_url, '.zip') . '.php';
            // activate_plugin($plugin_slug);

            $plugin_slug = basename($plugin_url, '.zip');
            $plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';

            // Periksa apakah file plugin ada
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
            if (!file_exists($plugin_path)) {
                throw new Exception('File plugin tidak ditemukan setelah ekstraksi: ' . $plugin_file);
            }

            // Aktifkan plugin
            $activation_result = activate_plugin($plugin_file);
            if (is_wp_error($activation_result)) {
                throw new Exception('Gagal mengaktifkan plugin: ' . $plugin_file . ' (' . $activation_result->get_error_message() . ')');
            }

            $results[] = [
                'url' => $plugin_url,
                'status' => 'success',
                'message' => 'Plugin berhasil diinstal dan diaktifkan.',
            ];
        } catch (Exception $e) {
            $results[] = [
                'url' => $plugin_url,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        } finally {
            if (!empty($temp_file) && file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }

    return rest_ensure_response($results);
}

function handle_import_json(WP_REST_Request $request) {
    $data = $request->get_json_params();

    if (empty($data) || !isset($data['website'])) {
        return new WP_Error('invalid_data', 'Data JSON tidak valid.', array('status' => 400));
    }

    try {
        // 1. Atur Homepage
        $homepage_data = $data['website']['homepage'];
        setup_homepage($homepage_data);

        // 2. Atur Kategori dan Subkategori
        $categories = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];
        setup_categories($categories);

        return rest_ensure_response(['success' => true, 'message' => 'Data berhasil diimpor.']);
    } catch (Exception $e) {
        return new WP_Error('import_failed', $e->getMessage(), array('status' => 500));
    }
}

function setup_homepage($homepage_data) {
    // Atur Judul dan Tagline
    update_option('blogname', $homepage_data['meta_title']);
    update_option('blogdescription', $homepage_data['meta_description']);

    // Tambahkan OpenGraph Data (RankMath)
    update_option('rank_math_titles_homepage_title', $homepage_data['meta_title']);
    update_option('rank_math_titles_homepage_description', $homepage_data['meta_description']);

    $og_data = $homepage_data['open_graph'];
    if (!empty($og_data['og_title'])) {
        update_option('rank_math_titles_homepage_facebook_title', sanitize_text_field($og_data['og_title']));
    }

    if (!empty($og_data['og_description'])) {
        update_option('rank_math_titles_homepage_facebook_description', sanitize_textarea_field($og_data['og_description']));
    }

    if (!empty($og_data['og_image'])) {
        update_option('rank_math_titles_homepage_facebook_image', esc_url_raw($og_data['og_image']));
    }

    if (!empty($og_data['og_url'])) {
        update_option('rank_math_titles_homepage_facebook_url', esc_url_raw($og_data['og_url']));
    }

    if (!empty($og_data['og_type'])) {
        update_option('rank_math_titles_homepage_facebook_type', sanitize_text_field($og_data['og_type']));
    }
    
    $twitter_data = $homepage_data['twitter_card'];

    // Validasi dan perbarui opsi untuk setiap atribut Twitter Card
    if (!empty($twitter_data['twitter_title'])) {
        update_option('rank_math_titles_homepage_twitter_title', sanitize_text_field($twitter_data['twitter_title']));
    }

    if (!empty($twitter_data['twitter_description'])) {
        update_option('rank_math_titles_homepage_twitter_description', sanitize_textarea_field($twitter_data['twitter_description']));
    }

    if (!empty($twitter_data['twitter_image'])) {
        update_option('rank_math_titles_homepage_twitter_image', esc_url_raw($twitter_data['twitter_image']));
    }

    if (!empty($twitter_data['twitter_card'])) {
        update_option('rank_math_titles_homepage_twitter_card', sanitize_text_field($twitter_data['twitter_card']));
    }

    // Atur halaman sebagai homepage
    $homepage_id = get_option('page_on_front');
    if (!$homepage_id) {
        $homepage_id = wp_insert_post([
            'post_title'   => $homepage_data['h1'],
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        update_option('page_on_front', $homepage_id);
        update_option('show_on_front', 'page');
    }

    // Tambahkan featured content
    if (!empty($homepage_data['featured_content'])) {
        setup_featured_content($homepage_id, $homepage_data['featured_content']);
    }

    if (!empty($homepage_data['h2_tags'])) {
        add_h2_tags_to_homepage($homepage_id, $homepage_data['h2_tags']);
    }

    if (!empty($homepage_data['focus_keywords'])) {
        add_focus_keywords($homepage_id, $homepage_data['focus_keywords']);
    }
    
}

function setup_categories($categories) {
    foreach ($categories as $category_data) {
        $category_id = wp_insert_term(
            $category_data['name'],
            'category',
            [
                'slug' => $category_data['slug'],
                'description' => $category_data['description']
            ]
        );

        if (is_wp_error($category_id)) {
            throw new Exception('Gagal membuat kategori: ' . $category_data['name']);
        }

        // Tambahkan subkategori
        if (!empty($category_data['subcategories'])) {
            foreach ($category_data['subcategories'] as $subcategory) {
                $subcategory_id = wp_insert_term(
                    $subcategory['name'],
                    'category',
                    [
                        'slug' => $subcategory['slug'],
                        'description' => $subcategory['meta_description'],
                        'parent' => $category_id['term_id']
                    ]
                );

                if (is_wp_error($subcategory_id)) {
                    throw new Exception('Gagal membuat subkategori: ' . $subcategory['name']);
                }
            }
        }
    }
}

function rank_math_add_breadcrumbs($page_id, $breadcrumbs) {
    if (!function_exists('update_post_meta')) {
        return;
    }

    $breadcrumbs_meta = [];
    foreach ($breadcrumbs as $crumb) {
        $breadcrumbs_meta[] = [
            'text' => $crumb['name'],
            'url'  => $crumb['url'],
        ];
    }

    update_post_meta($page_id, 'rank_math_breadcrumbs', $breadcrumbs_meta);
}

function setup_featured_content($homepage_id, $featured_content) {
    foreach ($featured_content as $content) {
        // Buat postingan untuk konten unggulan
        $post_id = wp_insert_post([
            'post_title'   => $content['title'],
            'post_content' => $content['description'],
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ]);

        if (is_wp_error($post_id)) {
            throw new Exception('Gagal membuat konten unggulan: ' . $content['title']);
        }

        // Tambahkan metadata SEO menggunakan RankMath jika tersedia
        if (function_exists('update_post_meta')) {
            update_post_meta($post_id, 'rank_math_focus_keyword', implode(',', $content['focus_keywords'] ?? []));
        }

        // Tambahkan URL canonical jika disediakan
        if (!empty($content['canonical_url'])) {
            update_post_meta($post_id, '_yoast_wpseo_canonical', $content['canonical_url']);
        }

        // Tambahkan gambar unggulan jika URL disediakan
        if (!empty($content['images'])) {
            foreach ($content['images'] as $image) {
                $attachment_id = media_sideload_image($image['src'], $post_id, $image['alt_text'], 'id');
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }
        }

        // Tambahkan link konten ke homepage menggunakan custom field
        if ($homepage_id) {
            add_post_meta($homepage_id, '_featured_content', [
                'title' => $content['title'],
                'url'   => $content['url'],
                'description' => $content['description'],
            ]);
        }
    }
}

function add_h2_tags_to_homepage($homepage_id, $h2_tags) {
    // Validasi input
    if (empty($homepage_id) || !is_numeric($homepage_id)) {
        throw new Exception('Invalid homepage ID.');
    }

    if (empty($h2_tags) || !is_array($h2_tags)) {
        throw new Exception('Invalid H2 tags data.');
    }

    // Ambil konten halaman saat ini
    $homepage = get_post($homepage_id);
    if (!$homepage || $homepage->post_type !== 'page') {
        throw new Exception('Homepage not found or is not a valid page.');
    }

    $existing_content = $homepage->post_content;

    // Buat konten baru
    $new_content = '';
    foreach ($h2_tags as $tag) {
        if (isset($tag['tag']) && !empty($tag['tag'])) {
            $new_content .= '<h2>' . esc_html($tag['tag']) . '</h2>';
        }

        if (isset($tag['description']) && !empty($tag['description'])) {
            $new_content .= '<p>' . esc_html($tag['description']) . '</p>';
        }
    }

    // Gabungkan konten baru dengan yang sudah ada
    $updated_content = $existing_content . "\n\n" . $new_content;

    // Perbarui konten halaman utama
    $update_result = wp_update_post([
        'ID'           => $homepage_id,
        'post_content' => $updated_content,
    ]);

    if (is_wp_error($update_result)) {
        throw new Exception('Failed to update homepage content: ' . $update_result->get_error_message());
    }

    return true; // Indikasikan keberhasilan
}

function add_focus_keywords($post_id, $keywords) {
    if (function_exists('update_post_meta')) {
        update_post_meta($post_id, 'rank_math_focus_keyword', implode(',', $keywords));
    }
}

function handle_import_json_logo(WP_REST_Request $request) {
    $data = $request->get_json_params();

    // Validasi dan proses logo
    if (isset($data['logos'])) {
        setup_jnews_logos_with_ids($data['logos']);
    }

    return new WP_REST_Response(['success' => true], 200);
}

function setup_jnews_logos_with_ids($logos) {
    // Validasi input
    if (!isset($logos) || !is_array($logos)) {
        throw new Exception('Logo data is missing or invalid.');
    }

    // Map logo settings ke Customizer
    $logo_settings = [
        'header_logo' => 'jnews_logo_header',
        'header_logo_retina' => 'jnews_logo_header_retina',
        'header_logo_dark_mode' => 'jnews_logo_header_darkmode',
        'header_logo_retina_dark_mode' => 'jnews_logo_header_retina_darkmode',
        'sticky_menu_logo' => 'jnews_logo_sticky_menu',
        'sticky_menu_logo_retina' => 'jnews_logo_sticky_menu_retina',
        'mobile_device_logo' => 'jnews_logo_mobile',
        'mobile_device_logo_dark_mode' => 'jnews_logo_mobile_darkmode',
        'mobile_device_logo_retina' => 'jnews_logo_mobile_retina',
        'mobile_device_logo_retina_dark_mode' => 'jnews_logo_mobile_retina_darkmode',
        'favicon' => 'jnews_favicon',
    ];

    // Iterasi setiap jenis logo
    foreach ($logo_settings as $key => $theme_mod_key) {
        if (isset($logos[$key]) && !empty($logos[$key])) {
            $attachment_id = intval($logos[$key]); // Konversi ke integer
            if (wp_attachment_is_image($attachment_id)) {
                set_theme_mod($theme_mod_key, $attachment_id);
            } else {
                error_log("Invalid attachment ID for $key: $attachment_id");
            }
        }
    }

    return true; // Indikasikan keberhasilan
}
