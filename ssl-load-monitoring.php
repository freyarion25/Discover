#!/usr/bin/env php
<?php
/**
 * Auto Rewrite Manager Script
 * Untuk mengelola proses monitoring
 */

$scriptPath = __DIR__ . '/auto_rewrite.php';
$pidFile = '/home/chiacundippal/ssl/auto_rewrite.pid';

function isRunning() {
    global $pidFile;
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if (posix_kill($pid, 0)) {
            return $pid;
        }
    }
    return false;
}

function startScript() {
    global $scriptPath;
    
    if (isRunning()) {
        echo "Script sudah berjalan!\n";
        return false;
    }
    
    // Jalankan script di background
    $command = "nohup php $scriptPath > /dev/null 2>&1 &";
    exec($command);
    
    echo "Script dimulai...\n";
    sleep(1);
    
    if (isRunning()) {
        echo "Script berhasil dijalankan\n";
        return true;
    } else {
        echo "Gagal menjalankan script\n";
        return false;
    }
}

function stopScript() {
    global $pidFile;
    
    $pid = isRunning();
    if ($pid) {
        exec("kill $pid");
        echo "Menghentikan proses PID: $pid\n";
        
        // Tunggu hingga proses berhenti
        for ($i = 0; $i < 10; $i++) {
            if (!isRunning()) {
                break;
            }
            sleep(1);
        }
        
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
        
        echo "Script berhasil dihentikan\n";
        return true;
    } else {
        echo "Script tidak sedang berjalan\n";
        return false;
    }
}

function statusScript() {
    $pid = isRunning();
    if ($pid) {
        echo "Script sedang BERJALAN dengan PID: $pid\n";
        
        // Cek log
        $logFile = '/home/chiacundippal/ssl/ssl.db.bu.log';
        if (file_exists($logFile)) {
            echo "\nLog terakhir:\n";
            echo "------------\n";
            $lines = file($logFile);
            $lastLines = array_slice($lines, -10);
            echo implode('', $lastLines);
        }
    } else {
        echo "Script TIDAK berjalan\n";
    }
}

// Menu sederhana
echo "Auto Rewrite Manager\n";
echo "===================\n";
echo "1. Start monitoring\n";
echo "2. Stop monitoring\n";
echo "3. Status\n";
echo "4. Exit\n";
echo "Pilihan (1-4): ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        startScript();
        break;
    case '2':
        stopScript();
        break;
    case '3':
        statusScript();
        break;
    case '4':
        echo "Keluar...\n";
        break;
    default:
        echo "Pilihan tidak valid\n";
}
