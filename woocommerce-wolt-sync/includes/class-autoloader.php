<?php
namespace Wolt_Sync;

class Autoloader {
    public static function register( $dir ) {
        // Ensure $dir ends with the separator for cleaner path construction
        $dir = rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR; 
        
        spl_autoload_register( function( $class ) use ( $dir ) {
            // Only handle classes within our namespace
            if ( strpos( $class, 'Wolt_Sync\\' ) !== 0 ) return;

            // 1. Convert Namespace Class Name (e.g., Wolt_Sync\Admin) to slug (e.g., admin)
            $rel = str_replace( 'Wolt_Sync\\', '', $class );
            $slug = strtolower( str_replace( '_', '-', $rel ) );

            // 2. Check for WordPress standard class-{slug}.php (e.g., class-admin.php)
            $path_class = $dir . 'class-' . $slug . '.php';
            
            if ( file_exists( $path_class ) ) {
                require_once $path_class;
                return; 
            }
            
            // 3. Check for simple {slug}.php (e.g., helpers.php)
            $path_simple = $dir . $slug . '.php';

            if ( file_exists( $path_simple ) ) {
                require_once $path_simple;
            }
        } );
    }
}