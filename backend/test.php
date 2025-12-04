<?php
// Test file to check PHP functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working!";
echo "<br>PHP Version: " . phpversion();
echo "<br>Current directory: " . __DIR__;

// Check if vendor directory exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<br>Vendor autoload exists";
} else {
    echo "<br>Vendor autoload NOT found";
}

// Check if required extensions are loaded
$required_extensions = ['curl', 'json', 'zip'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<br>$ext extension is loaded";
    } else {
        echo "<br>$ext extension is NOT loaded";
    }
}
?> 