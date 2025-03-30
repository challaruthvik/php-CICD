<?php
/**
 * Server Path Info
 * This script outputs information about the server environment and file paths
 */

header('Content-Type: text/plain');

echo "===== SERVER PATH INFO =====\n\n";

echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Server OS: " . PHP_OS . "\n\n";

echo "--- Path Information ---\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "\n";
echo "Current Directory: " . getcwd() . "\n";
echo "Real Path: " . realpath('.') . "\n\n";

echo "--- Server Variables ---\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "\n\n";

echo "--- Directory Structure ---\n";
echo "Parent directory contents:\n";
$parentContents = scandir('..');
foreach ($parentContents as $item) {
    $path = '../' . $item;
    $type = is_dir($path) ? 'DIR' : 'FILE';
    echo "- {$type}: {$item}\n";
}

echo "\nCurrent directory contents:\n";
$currentContents = scandir('.');
foreach ($currentContents as $item) {
    $path = './' . $item;
    $type = is_dir($path) ? 'DIR' : 'FILE';
    echo "- {$type}: {$item}\n";
}

echo "\n--- PHP Directories ---\n";
echo "include_path: " . get_include_path() . "\n";
echo "extension_dir: " . ini_get('extension_dir') . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";
echo "sys_temp_dir: " . sys_get_temp_dir() . "\n\n";

echo "--- PHP Info Summary ---\n";
ob_start();
phpinfo(INFO_CONFIGURATION | INFO_MODULES);
$phpinfo = ob_get_clean();

// Extract just the paths from phpinfo
$paths = array();
if (preg_match_all('/([^=]*) => (.*)/', strip_tags($phpinfo), $matches)) {
    for ($i = 0; $i < count($matches[1]); $i++) {
        $key = trim($matches[1][$i]);
        $value = trim($matches[2][$i]);
        if (strpos($key, 'path') !== false || strpos($key, 'dir') !== false || strpos($key, 'root') !== false) {
            $paths[$key] = $value;
        }
    }
}

foreach ($paths as $key => $value) {
    echo "{$key} => {$value}\n";
}

echo "\n===== END SERVER PATH INFO =====\n";