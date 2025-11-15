<?php
namespace Wolt_Sync;

class Admin {
    private static $instance;
    const MENU_SLUG = 'wolt-sync-settings';

    public $options_key = 'wolt_sync_options';

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        
        // Handle the maintenance submission to clear cache
        if ( isset( $_POST['wolt_sync_acf_clear_cache'] ) ) {
            add_action( 'admin_init', [ $this, 'handle_cache_clear_submission' ] );
        }
    }

    public function menu() {
        // 1. ADD TOP-LEVEL MENU PAGE (Settings)
        add_menu_page(
            'Wolt Price Sync Settings', 
            'Wolt Sync',                
            'manage_woocommerce',       
            self::MENU_SLUG,            
            [ $this, 'page' ],          
            'dashicons-money-alt',      
            55                          
        );
        
        // 2. EXPLICITLY ADD SETTINGS PAGE AS THE FIRST SUBMENU
        add_submenu_page(
            self::MENU_SLUG,            
            'Wolt Sync Settings',       
            'Settings',                 
            'manage_woocommerce',       
            self::MENU_SLUG,            
            [ $this, 'page' ]           // Loads views/admin-settings.php
        );
        
        // 3. ADD LOGS SUBMENU PAGE
        add_submenu_page(
            self::MENU_SLUG,            
            'Wolt Sync Logs',           
            'Logs',                     
            'manage_woocommerce',       
            'wolt-sync-logs',           
            [ $this, 'logs_page' ]      // Loads views/admin-logs.php
        );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'wolt-sync' ) === false ) return;
        
        // Enqueue simple CSS file
        wp_enqueue_style( 'wolt-sync-admin-css', WOLT_SYNC_URL . 'assets/admin.css', [], '1.0.0' );

        // Enqueue simple JS file (relies on jQuery for AJAX worker)
        wp_enqueue_script( 
            'wolt-sync-admin-js', 
            WOLT_SYNC_URL . 'assets/admin.js', 
            ['jquery'], 
            '1.0.0', 
            true
        );
        
        // Localize script for AJAX communication
        wp_localize_script( 'wolt-sync-admin-js', 'WoltSync', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wolt_sync_manual' ),
        ] );
    }
    
    public function handle_cache_clear_submission() {
         if ( check_admin_referer( 'wolt_sync_clear_cache_action', 'wolt_sync_acf_cache_nonce' ) ) {
             if ( current_user_can( 'manage_woocommerce' ) ) {
                 $this->clear_all_transients();
                 // Redirect back to the settings page with a status message
                 wp_safe_redirect( add_query_arg( ['page' => self::MENU_SLUG, 'status' => 'cache-cleared'], admin_url('admin.php') ) );
                 exit;
             }
         }
    }

    public function clear_all_transients() {
        global $wpdb;
        // The transients are named 'wolt_sync_{product_id}'
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like( '_transient_wolt_sync_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_wolt_sync_' ) . '%'
        );
        $wpdb->query( $sql );
    }

    public function page() {
        // Includes the PHP dashboard view
        include WOLT_SYNC_PATH . 'views/admin-settings.php';
    }

    public function logs_page() {
        // Includes the PHP logs view
        include WOLT_SYNC_PATH . 'views/admin-logs.php';
    }

    public static function get_options() {
        $defaults = [
            'enabled' => 1,
            'hour' => 6,
            'batch_size' => 20,
            'cache_ttl' => 43200,
        ];
        $raw = get_option( 'wolt_sync_options', [] );
        return wp_parse_args( $raw, $defaults );
    }
}