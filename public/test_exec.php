<?php
// Debug Script for Exec Permissions
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$nodePath = '/home/u896481526/node-v22.18.0-linux-x64/bin/node';

echo "<h2>Server Environment Debug</h2>";
echo "<strong>User:</strong> " . get_current_user() . "<br>";
echo "<strong>UID:</strong> " . getmyuid() . "<br>";

echo "<h3>Checking Node Path</h3>";
echo "Target Path: <code>$nodePath</code><br>";

if (file_exists($nodePath)) {
    echo "✅ File Exists.<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($nodePath)), -4) . "<br>";
} else {
    echo "❌ <strong>FILE NOT FOUND</strong> (Check path?)<br>";
}

echo "<h3>Testing Exec</h3>";

if (!function_exists('exec')) {
    echo "❌ <strong>EXEC FUNCTION DISABLED</strong> in php.ini<br>";
    exit;
}

$output = [];
$returnVar = -1;

// Try running node version
exec("$nodePath -v 2>&1", $output, $returnVar);

echo "<strong>Command:</strong> <code>$nodePath -v</code><br>";
echo "<strong>Return Code:</strong> $returnVar (0 = Success)<br>";
echo "<strong>Output:</strong><pre>";
print_r($output);
echo "</pre>";
?>
