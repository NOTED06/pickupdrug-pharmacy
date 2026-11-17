<?php

require_once(__DIR__ . '/ChatbotCache.php');
require_once(__DIR__ . '/../config.php');

global $conn;

$cache = new ChatbotCache($conn);
$result = $cache->clearAllCache();
echo "Cache cleared!\n";
print_r($result);
