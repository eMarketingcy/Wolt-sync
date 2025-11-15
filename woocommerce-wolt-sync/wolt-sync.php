<?php
/**
 * Plugin Name: WooCommerce Wolt Price Sync (ACF Advanced)
 * Description: Robust daily sync of WooCommerce prices & images from ACF Wolt product URLs. Batching, caching, AJAX progress, log viewer, fallback scraping.
 * Version: 2.0.0
 * Author: eMarketing Cyprus
 * Text Domain: wolt-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WOLT_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOLT_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'WOLT_SYNC_FILE', __FILE__ );
define( 'WOLT_SYNC_MIN_PHP', '7.4' );

require_once WOLT_SYNC_PATH . 'includes/class-autoloader.php';
Wolt_Sync\Autoloader::register( WOLT_SYNC_PATH . 'includes' );

// Initialize core classes
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'get_field' ) ) {
        // Dependencies missing
        return;
    }

    Wolt_Sync\Admin::instance();
    Wolt_Sync\Cron::instance();
    Wolt_Sync\Ajax::instance();
    Wolt_Sync\Logger::instance();
} );

// Activation / deactivation
register_activation_hook( __FILE__, function() {
    Wolt_Sync\Logger::create_table();
    Wolt_Sync\Cron::activate();
} );
register_deactivation_hook( __FILE__, function() {
    Wolt_Sync\Cron::deactivate();
} );

/**
 * Add AVIF file extension and MIME type to allowed uploads.
 * This is the minimum requirement to allow the file to be sideloaded.
 * Note: Full processing requires WordPress 6.5+ and supporting server libraries.
 */
function wolt_sync_allow_avif_uploads( $mime_types ) {
    $mime_types['avif'] = 'image/avif';
    return $mime_types;
}
add_filter( 'upload_mimes', 'wolt_sync_allow_avif_uploads' );