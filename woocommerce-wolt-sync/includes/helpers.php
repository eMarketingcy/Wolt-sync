<?php
namespace Wolt_Sync;

class Helpers {
    public static function clean_image_url( $url ) {
        if ( empty( $url ) ) return '';
        $parts = parse_url( $url );
        if ( ! isset( $parts['scheme'] ) ) return $url;
        $clean = $parts['scheme'] . '://' . $parts['host'] . ( $parts['path'] ?? '' );
        return rtrim( $clean, '/' );
    }
}
