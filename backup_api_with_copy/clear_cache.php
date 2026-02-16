<?php
// Очистка opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared!\n";
} else {
    echo "OPcache is not enabled\n";
}

// Очистка stat cache
clearstatcache(true);
echo "Stat cache cleared!\n";
