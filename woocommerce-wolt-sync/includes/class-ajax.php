<?php
namespace Wolt_Sync;

class Ajax {
    private static $instance;
    public static function instance() {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Endpoint to initialize the queue
        add_action( 'wp_ajax_wolt_sync_manual', [ $this, 'manual' ] );
        // Endpoint for JS to poll progress
        add_action( 'wp_ajax_wolt_sync_progress', [ $this, 'progress' ] );
        // NEW: Endpoint for JS to trigger the single product processing
        add_action( 'wp_ajax_wolt_sync_worker', [ $this, 'worker' ] );
    }

    public function manual() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'no' );
        check_ajax_referer( 'wolt_sync_manual', 'nonce' );

        // 1. Get all linked product IDs
        $q = new \WP_Query([ 'post_type' => 'product', 'posts_per_page' => -1, 'meta_query' => [[ 'key'=>'wolt_link','value'=>'','compare'=>'!=' ]], 'fields'=>'ids' ]);
        $ids = $q->posts ?: [];
        if ( empty( $ids ) ) wp_send_json_error( 'no-products' );

        // 2. Store queue and progress transients
        $key = 'wolt_sync_queue_' . get_current_user_id();
        set_transient( $key, $ids, 15 * MINUTE_IN_SECONDS );
        set_transient( $key . '_progress', [ 'total' => count($ids), 'done' => 0, 'errors' => 0 ], 15 * MINUTE_IN_SECONDS );

        // 3. Return success with the queue key
        wp_send_json_success( [ 'queue_key' => $key, 'total' => count($ids) ] );
    }

    public function progress() {
        check_ajax_referer( 'wolt_sync_manual', 'nonce' );
        $key = sanitize_text_field( $_POST['queue_key'] ?? '' );
        if ( ! $key ) wp_send_json_error( 'no-key' );
        $prog = get_transient( $key . '_progress' );
        if ( ! $prog ) wp_send_json_success( [ 'done' => 0, 'total' => 0 ] );
        wp_send_json_success( $prog );
    }

    /**
     * NEW WORKER: Processes one product ID from the queue.
     */
    public function worker() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'no' );
        check_ajax_referer( 'wolt_sync_manual', 'nonce' );

        $key = sanitize_text_field( $_POST['queue_key'] ?? '' );
        if ( ! $key ) wp_send_json_error( 'no-key' );

        // 1. Retrieve the queue and progress data
        $ids_queue = get_transient( $key );
        $progress = get_transient( $key . '_progress' );

        if ( empty( $ids_queue ) || empty( $progress ) ) {
            wp_send_json_success( ['finished' => true] ); // Signal completion
        }

        // 2. Pop the first ID to process
        $product_id = array_shift( $ids_queue );
        
        if ( ! $product_id ) {
            wp_send_json_success( ['finished' => true] ); // Queue is empty
        }

        // 3. Process the single product
        $updater = Product_Updater::instance();
        // Since the process_single method logs its own errors, we wrap it in a try/catch for general safety
        try {
            // Note: Caching TTL is passed to the updater for transient logic
            $opts = Admin::get_options();
            $updater->process_single( $product_id, intval( $opts['cache_ttl'] ) );
            $error_count = 0; // The method doesn't return status, so assume success unless we check logs

        } catch (\Exception $e) {
            Logger::log( $product_id, 'fatal', 'Uncaught Exception: ' . $e->getMessage() );
            $error_count = 1;
        }

        // 4. Update progress
        $progress['done']++;
        $progress['errors'] += $error_count;

        // 5. Save updated queue and progress
        set_transient( $key, $ids_queue, 15 * MINUTE_IN_SECONDS );
        set_transient( $key . '_progress', $progress, 15 * MINUTE_IN_SECONDS );

        // 6. Respond to keep the worker running
        wp_send_json_success( [
            'product_id' => $product_id,
            'progress' => $progress,
            'finished' => false,
            'remaining' => count($ids_queue)
        ] );
    }
}