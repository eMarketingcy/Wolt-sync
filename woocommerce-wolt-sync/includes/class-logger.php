<?php
namespace Wolt_Sync;

class Logger {
    private static $instance;
    const TABLE = 'wolt_sync_logs';

    public static function instance() {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public static function create_table() {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$tbl} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NULL,
            level VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function log( $product_id, $level, $message ) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $wpdb->insert( $tbl, [ 'product_id' => $product_id, 'level' => $level, 'message' => $message, 'created_at' => current_time( 'mysql' ) ], [ '%d', '%s', '%s', '%s' ] );
    }

    // Simple paginated fetch
    public static function get_logs( $page = 1, $per = 50 ) {
        global $wpdb;
        $tbl = $wpdb->prefix . self::TABLE;
        $offset = max( 0, ( $page - 1 ) * $per );
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per, $offset ) );
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" );
        return [ 'rows' => $rows, 'total' => intval( $total ) ];
    }
}
