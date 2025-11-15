<?php
// Namespace/Class definitions are handled by the autoloader.
use Wolt_Sync\Admin;

$opts = Admin::get_options();

// Handle settings save submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'wolt_sync_save', 'wolt_sync_nonce' ) ) {
    $opts['enabled'] = isset( $_POST['enabled'] ) ? 1 : 0;
    $opts['hour'] = intval( $_POST['hour'] );
    $opts['batch_size'] = intval( $_POST['batch_size'] );
    $opts['cache_ttl'] = intval( $_POST['cache_ttl'] );
    update_option( 'wolt_sync_options', $opts );
    do_action( 'init' ); 
    echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
}

// Handle cache cleared status message
if ( isset($_GET['status']) && $_GET['status'] === 'cache-cleared' ) {
    echo '<div class="notice notice-success"><p>All Wolt Sync product caches have been cleared, forcing a fresh scrape on the next run.</p></div>';
}

// Get current cron status
$next_run = wp_next_scheduled( 'wolt_sync_run_batch' );
$status_class = $next_run ? 'dashicons-yes-alt' : 'dashicons-no-alt';
$status_color = $next_run ? '#00A040' : '#DC3232'; // Green or Red
$status_text = $next_run ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run ) : 'Not Scheduled (Check settings)';

?>
<div class="wrap">
    <h1>Wolt Sync Dashboard</h1>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        
        <div class="card" style="border: 1px solid #c3c4c7; padding: 15px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <h2 style="font-size: 1.2em; margin: 0;"><span class="dashicons dashicons-update" style="color: #0073aa;"></span> Manual Synchronization</h2>
                <p class="description">Trigger an immediate sync of all linked WooCommerce products.</p>
            </div>
            <div class="card-content">
                <p style="margin-bottom: 15px; font-style: italic;">
                    The sync process will run asynchronously. Keep this window open for the progress bar to complete.
                </p>
                
                <button id="wolt-sync-start" class="button button-primary">Start Manual Sync (AJAX)</button>
                
                <div id="wolt-sync-progress" style="margin-top: 15px; display: none; height: 35px; border: 1px solid #ccc; background: #f0f0f0;">
                    <div id="wolt-sync-bar" style="width:0; height:100%; background:#0073aa; transition: width 0.4s; float: left;"></div>
                    <div id="wolt-sync-text" style="line-height: 35px; text-align: center; color: #333; position: absolute; width: calc(100% - 20px);">Initializing...</div>
                </div>
            </div>
        </div>
        
        <div class="card" style="border: 1px solid #c3c4c7; padding: 15px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <h2 style="font-size: 1.2em; margin: 0;"><span class="dashicons dashicons-calendar-alt" style="color: #0073aa;"></span> Automated Schedule Settings</h2>
            </div>
            <div class="card-content">
                <form method="post">
                    <?php wp_nonce_field( 'wolt_sync_save', 'wolt_sync_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Status (Next Run)</th>
                            <td>
                                <span class="dashicons <?php echo $status_class; ?>" style="color: <?php echo $status_color; ?>;"></span>
                                <strong><?php echo $status_text; ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Daily Cron</th>
                            <td>
                                <input type="checkbox" name="enabled" value="1" <?php checked( $opts['enabled'], 1 ); ?> />
                                <p class="description">Enable or disable the daily scheduled sync process.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Schedule Hour (UTC)</th>
                            <td>
                                <input type="number" name="hour" min="0" max="23" value="<?php echo esc_attr( $opts['hour'] ); ?>" style="width: 80px;" />
                                <p class="description">Hour of the day (0-23) when the sync should begin running batches.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Batch Size</th>
                            <td>
                                <input type="number" name="batch_size" min="1" max="200" value="<?php echo esc_attr( $opts['batch_size'] ); ?>" style="width: 80px;" />
                                <p class="description">Products processed per run (lower this on shared hosting).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Cache TTL (seconds)</th>
                            <td>
                                <input type="number" name="cache_ttl" min="3600" value="<?php echo esc_attr( $opts['cache_ttl'] ); ?>" style="width: 120px;" />
                                <p class="description">How long to cache a successfully scraped product's result (Default: 43200s / 12 hours).</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( 'Save Settings' ); ?>
                </form>
            </div>
        </div>

        <div class="card" style="border: 1px solid #c3c4c7; padding: 15px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div class="card-header" style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                <h2 style="font-size: 1.2em; margin: 0;"><span class="dashicons dashicons-database-remove" style="color: #dc3232;"></span> Maintenance</h2>
            </div>
            <div class="card-content">
                <p style="margin-bottom: 15px;">Clear the product cache if you suspect the product pages have changed drastically and need an immediate full rescan.</p>
                <form method="post">
                     <?php wp_nonce_field( 'wolt_sync_clear_cache_action', 'wolt_sync_acf_cache_nonce' ); ?>
                     <input type="hidden" name="wolt_sync_acf_clear_cache" value="1" />
                     <?php submit_button( 'Clear All Product Caches', 'delete', 'wolt_sync_acf_clear_cache', false ); ?>
                </form>
            </div>
        </div>

    </div>

    <p style="margin-top: 20px;">Detailed synchronization errors are visible on the <a href="<?php echo admin_url('admin.php?page=wolt-sync-logs'); ?>">Logs page</a>.</p>
</div>