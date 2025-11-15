<?php
namespace Wolt_Sync;

class Cron {
    private static $instance;
    const HOOK = 'wolt_sync_run_batch';

    public static function instance() {
        if ( ! self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    public static function activate() {
        $opts = Admin::get_options();
        $hour = intval( $opts['hour'] );
        $now = current_time( 'timestamp' );
        $next = strtotime( date( 'Y-m-d', $now ) . " {$hour}:00:00", $now );
        if ( $next <= $now ) $next += DAY_IN_SECONDS;
        if ( ! wp_next_scheduled( self::HOOK ) ) wp_schedule_event( $next, 'daily', self::HOOK );
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) wp_unschedule_event( $timestamp, self::HOOK );
    }

    private function __construct() {
        add_action( self::HOOK, [ $this, 'run' ] );
    }

    public function run() {
        // instantiate scraper/updater
        $updater = Product_Updater::instance();
        $updater->process_batch();
    }
}
