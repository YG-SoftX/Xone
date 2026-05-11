<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "GD Enabled: " . (extension_loaded('gd') ? 'Yes' : 'No') . "\n";
echo "Imagick Enabled: " . (extension_loaded('imagick') ? 'Yes' : 'No') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
if (extension_loaded('gd')) {
    $info = gd_info();
    echo "GD WebP Support: " . ($info['WebP Support'] ? 'Yes' : 'No') . "\n";
}
