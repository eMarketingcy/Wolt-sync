<?php
namespace Wolt_Sync;

class Scraper {
    // Single responsibility: fetch HTML and extract price + image

    public static function fetch( $url ) {
        // try wp_remote_get first with a realistic UA
        $args = [ 'timeout' => 30, 'headers' => [ 'User-Agent' => self::user_agent() ] ];
        $resp = wp_remote_get( $url, $args );
        if ( is_wp_error( $resp ) ) {
            // fallback to curl if available
            return self::curl_fetch( $url );
        }
        $code = wp_remote_retrieve_response_code( $resp );
        if ( $code !== 200 ) {
            // attempt mobile version fallback (common pattern)
            $mobile = self::mobile_variant( $url );
            if ( $mobile ) return self::fetch_mobile( $mobile );
            return [ 'error' => 'HTTP ' . $code ];
        }
        $html = wp_remote_retrieve_body( $resp );
        return self::parse( $html, $url );
    }

    private static function curl_fetch( $url ) {
        if ( ! function_exists( 'curl_init' ) ) return [ 'error' => 'Transport error and no curl' ];
        $ch = curl_init(); 
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_USERAGENT, self::user_agent() );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        $body = curl_exec( $ch );
        $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        if ( $code !== 200 ) return [ 'error' => 'HTTP ' . $code ];
        return self::parse( $body, $url );
    }

    public static function user_agent() {
        // Realistic modern UA to reduce blocking
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';
    }

    private static function mobile_variant( $url ) {
        // Try to create a mobile friendly url if pattern matches
        return $url; // for Wolt same URL often works but mobile UA might produce different markup
    }

    private static function fetch_mobile( $url ) {
        $args = [ 'timeout' => 30, 'headers' => [ 'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15' ] ];
        $resp = wp_remote_get( $url, $args );
        if ( is_wp_error( $resp ) ) return [ 'error' => $resp->get_error_message() ];
        $code = wp_remote_retrieve_response_code( $resp );
        if ( $code !== 200 ) return [ 'error' => 'HTTP ' . $code ];
        $html = wp_remote_retrieve_body( $resp );
        return self::parse( $html, $url );
    }

    public static function parse( $html, $url ) {
        $doc = new \DOMDocument();
        libxml_use_internal_errors( true );
        $loaded = @$doc->loadHTML( $html );
        libxml_clear_errors();
        if ( ! $loaded ) return [ 'error' => 'Failed to parse HTML' ];
        $xpath = new \DOMXPath( $doc );

        $result = [ 'price' => null, 'image_url' => null, 'available' => false, 'error' => null ];

        // Price
        $price_nodes = $xpath->query( '//span[@data-test-id="product-modal.price"]' );
        if ( $price_nodes->length ) {
            $raw = trim( $price_nodes->item(0)->nodeValue );
            $raw = str_replace( [ '€', '\u00A0', ',' ], [ '', '', '.' ], $raw );
            $clean = preg_replace( '/[^\d\.]/', '', $raw );
            if ( $clean !== '' ) {
                $result['price'] = floatval( $clean );
                $result['available'] = true;
            }
        }

        // Image
        $img_nodes = $xpath->query( '//button[@data-test-id="product-modal.main-image.product-image"]//img' );
        if ( $img_nodes->length ) {
            $img = $img_nodes->item(0);
            $src = $img->getAttribute( 'src' );
            if ( empty( $src ) ) {
                $srcset = $img->getAttribute( 'srcset' );
                if ( $srcset ) {
                    $parts = preg_split('/,\s*/', trim( $srcset ) );
                    $last = end( $parts );
                    $last = preg_replace( '/\s+\dw$/', '', $last );
                    $src = trim( $last );
                }
            }
            if ( $src ) {
                if ( parse_url( $src, PHP_URL_SCHEME ) === null ) {
                    $base = parse_url( $url );
                    $src = $base['scheme'] . '://' . $base['host'] . '/' . ltrim( $src, '/' );
                }
                $result['image_url'] = $src;
            }
        }

        return $result;
    }
}