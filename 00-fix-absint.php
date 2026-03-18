<?php
/**
 * Plugin Name: Fix Absint Function
 * Description: Memastikan fungsi absint() tersedia sebelum media.php di-load
 * Version: 1.0
 */

// Definisi fungsi absint jika belum ada
if ( ! function_exists( 'absint' ) ) {
    /**
     * Converts a value to non-negative integer.
     *
     * @param mixed $maybeint Data to convert to non-negative integer.
     * @return int Non-negative integer.
     */
    function absint( $maybeint ) {
        return abs( (int) $maybeint );
    }
}

// Force load functions.php lebih awal
add_action( 'muplugins_loaded', function() {
    if ( function_exists( 'absint' ) ) {
        return;
    }
    
    // Coba load manual
    $functions_file = ABSPATH . WPINC . '/functions.php';
    if ( file_exists( $functions_file ) ) {
        require_once $functions_file;
    }
}, 0 );
