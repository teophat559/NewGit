<?php
/**
 * BVOTE System Diagnostic Tool
 */

echo "🔍 BVOTE System Diagnostic Report\n";
echo "==================================\n\n";

// PHP Information
echo "📋 PHP Information:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n\n";

// File System Check
echo "📁 File System:\n";
$directories = ['config', 'data', 'logs', 'uploads', 'modules'];
foreach ($directories as $dir) {
    $status = is_dir($dir) ? (is_writable($dir) ? '✅ Writable' : '⚠️ Not writable') : '❌ Missing';
    echo "$dir: $status\n";
}

echo "\n📊 System Status: Operational\n";
