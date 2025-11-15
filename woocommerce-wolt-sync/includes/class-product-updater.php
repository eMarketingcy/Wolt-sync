<?php
namespace Wolt_Sync;

class Product_Updater {
    private static $instance;
    public static function instance() {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public function process_batch() {
        $opts = Admin::get_options();
        $batch = max( 1, intval( $opts['batch_size'] ) );
        $cache_ttl = intval( $opts['cache_ttl'] );

        $q = new \WP_Query([
            'post_type' => 'product',
            'posts_per_page' => $batch,
            'meta_query' => [[ 'key' => 'wolt_link', 'value' => '', 'compare' => '!=' ]],
            'fields' => 'ids',
        ]);

        if ( empty( $q->posts ) ) return;

        foreach ( $q->posts as $pid ) {
            $this->process_single( $pid, $cache_ttl );
            // throttle to reduce rate-limiting (adjust as needed)
            sleep(1);
        }
    }

    public function process_single( $product_id, $cache_ttl = 43200 ) {
        $url = get_field( 'wolt_link', $product_id );
        if ( ! $url ) {
            Logger::log( $product_id, 'error', 'Missing Wolt URL' );
            return;
        }

        $tkey = 'wolt_sync_' . $product_id;
        $cached = get_transient( $tkey );
        if ( $cached ) $data = $cached;
        else {
            $data = Scraper::fetch( $url );
            if ( empty( $data['error'] ) ) set_transient( $tkey, $data, $cache_ttl );
        }

        if ( ! empty( $data['error'] ) ) {
            Logger::log( $product_id, 'error', 'Scraping error: ' . $data['error'] );
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) { Logger::log( $product_id, 'error', 'WC product not found' ); return; }

        // Price handling
        if ( isset( $data['price'] ) && is_numeric( $data['price'] ) ) {
            $new = floatval( $data['price'] );
            $current_regular = floatval( $product->get_regular_price() );
            if ( $new !== $current_regular ) {
                $product->set_regular_price( $new );
                if ( ! $product->get_sale_price() ) $product->set_price( $new );
                $product->save();
                Logger::log( $product_id, 'info', 'Price updated to ' . $new );
            }
        }

        // Image handling
        if ( ! empty( $data['image_url'] ) ) {
            $current_id = get_post_thumbnail_id( $product_id );
            $current_url = $current_id ? wp_get_attachment_url( $current_id ) : '';
            $clean_current = Helpers::clean_image_url( $current_url );
            $clean_new = Helpers::clean_image_url( $data['image_url'] );
            
            if ( empty( $current_url ) || $clean_current !== $clean_new ) {
                $ok = $this->replace_image( $product_id, $data['image_url'] );
                if ( $ok ) {
                    Logger::log( $product_id, 'info', 'Image updated' );
                }
            }
        }
    }

    private function replace_image( $product_id, $image_url ) {
        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        
        // 1. Download the image
        $tmp = download_url( $image_url );
        if ( is_wp_error( $tmp ) ) {
            Logger::log( $product_id, 'error', 'Image download failed: ' . $tmp->get_error_message() );
            return false;
        }
        
        // --- CRITICAL FIX: Ensure the file is named with the .avif extension ---
        $file_name = 'wolt-sync-' . $product_id . '.avif';
        $file = [ 'tmp_name' => $tmp, 'name' => $file_name ];
        // -----------------------------------------------------------------------
        
        // 2. Sideload into the Media Library
        $aid = media_handle_sideload( $file, $product_id );
        
        if ( is_wp_error( $aid ) ) { 
            @unlink( $file['tmp_name'] ); 
            Logger::log( $product_id, 'error', 'Image sideload failed: ' . $aid->get_error_message() );
            return false; 
        }
        
        // 3. Set as featured image
        set_post_thumbnail( $product_id, $aid );
        return true;
    }
}