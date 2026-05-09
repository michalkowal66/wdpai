<?php

// Fix for paths when running from CLI
chdir(__DIR__ . '/..');

require_once 'src/repositories/BookingRepository.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting NO_SHOW booking cleanup...\n";

try {
    $repo = new BookingRepository();
    $rowCount = $repo->autoCancelNoShows(); 
    echo "[" . date('Y-m-d H:i:s') . "] Successfully freed $rowCount expired desk(s).\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR during cleanup: " . $e->getMessage() . "\n";
}
