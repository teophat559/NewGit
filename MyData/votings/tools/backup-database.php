<?php
/**
 * BVOTE Database Backup Script
 * Tạo backup database tự động
 */

require_once __DIR__ . '/../bootstrap.php';

use BVOTE\Core\Logger;

echo "💾 BVOTE Database Backup Starting...\n";
echo "====================================\n\n";

try {
    // Configuration
    $backupDir = __DIR__ . '/../storage/backups';
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = "bvote_backup_{$timestamp}.sql";
    $backupPath = $backupDir . '/' . $backupFile;

    // Create backup directory if not exists
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Database connection details
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbName = $_ENV['DB_DATABASE'] ?? 'bvote_system';
    $dbUser = $_ENV['DB_USERNAME'] ?? 'root';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    echo "📊 Database: {$dbName} on {$dbHost}\n";
    echo "📁 Backup file: {$backupFile}\n\n";

    // Create backup using mysqldump
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers --add-drop-table --create-options --quick --lock-tables=false %s > %s',
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($backupPath)
    );

    echo "🔄 Creating backup...\n";
    $output = [];
    $returnCode = 0;

    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        // Compress backup file
        $compressedFile = $backupPath . '.gz';
        $gz = gzopen($compressedFile, 'w9');
        gzwrite($gz, file_get_contents($backupPath));
        gzclose($gz);

        // Remove uncompressed file
        unlink($backupPath);

        // Get file size
        $fileSize = filesize($compressedFile);
        $fileSizeFormatted = formatBytes($fileSize);

        echo "✅ Backup created successfully!\n";
        echo "📁 File: {$compressedFile}\n";
        echo "📏 Size: {$fileSizeFormatted}\n";

        // Log success
        Logger::info('Database backup created successfully', [
            'file' => $compressedFile,
            'size' => $fileSize,
            'size_formatted' => $fileSizeFormatted
        ]);

        // Clean old backups (keep last 30 days)
        cleanOldBackups($backupDir, 30);

        echo "\n🧹 Old backups cleaned\n";

    } else {
        throw new Exception("mysqldump failed with return code: {$returnCode}");
    }

} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
    Logger::error('Database backup failed: ' . $e->getMessage());
    exit(1);
}

echo "\n🎉 Database backup completed successfully!\n";

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Clean old backup files
 */
function cleanOldBackups(string $backupDir, int $daysToKeep): void {
    $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

    $files = glob($backupDir . '/bvote_backup_*.sql.gz');

    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
            echo "🗑️  Deleted old backup: " . basename($file) . "\n";
        }
    }
}
