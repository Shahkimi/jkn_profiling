<?php
/**
 * Cache Warmer Script
 * This script ensures the cache is always populated with fresh data
 * Run this script via cron job every hour to maintain cache freshness
 */

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Cache configuration
$cacheFile = __DIR__ . '/cache/facility_data.json';
$cacheExpiry = (int)(getenv('CACHE_EXPIRY') ?: 3600); // 1 hour default

function warmCache($cacheFile) {
    $sheetUrl = getenv('GOOGLE_SHEETS_URL');
    
    if (!$sheetUrl) {
        error_log("Cache Warmer: GOOGLE_SHEETS_URL not configured");
        return false;
    }
    
    $context = stream_context_create([
        'http' => [
            'timeout' => (int)(getenv('GOOGLE_SHEETS_TIMEOUT') ?: 10),
            'user_agent' => getenv('HTTP_USER_AGENT') ?: getenv('USER_AGENT')
        ]
    ]);
    
    echo "Cache Warmer: Fetching data from Google Sheets...\n";
    $csvData = @file_get_contents($sheetUrl, false, $context);
    
    if ($csvData === FALSE) {
        error_log("Cache Warmer: Failed to fetch data from Google Sheets");
        return false;
    }
    
    $hospitals = [];
    $tempFile = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($tempFile, $csvData);
    
    $handle = fopen($tempFile, 'r');
    $allRows = [];
    
    while (($row = fgetcsv($handle, 10000, ',', '"')) !== FALSE) {
        $allRows[] = $row;
    }
    
    fclose($handle);
    unlink($tempFile);
    
    $headerIndex = -1;
    $headers = null;
    
    foreach ($allRows as $index => $row) {
        if (isset($row[0]) && trim($row[0]) === 'PTJ') {
            $headerIndex = $index;
            $headers = array_map('trim', $row);
            break;
        }
    }
    
    if ($headerIndex !== -1 && $headers) {
        $dataRows = array_slice($allRows, $headerIndex + 1);
        
        foreach ($dataRows as $row) {
            if (empty(array_filter($row, fn($val) => !empty(trim($val ?? ''))))) {
                continue;
            }
            
            if (!isset($row[0]) || empty(trim($row[0]))) {
                continue;
            }
            
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            
            $row = array_slice($row, 0, count($headers));
            
            $cleanRow = array_map(function($val) {
                $val = trim($val ?? '');
                return $val;
            }, $row);
            
            $hospitals[] = array_combine($headers, $cleanRow);
        }
    }
    
    if (!empty($hospitals)) {
        $cacheData = [
            'timestamp' => time(),
            'hospitals' => $hospitals
        ];
        
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        if (file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT))) {
            echo "Cache Warmer: Successfully updated cache with " . count($hospitals) . " hospitals\n";
            return true;
        } else {
            error_log("Cache Warmer: Failed to write cache file");
            return false;
        }
    } else {
        error_log("Cache Warmer: No hospital data found");
        return false;
    }
}

function isCacheExpired($cacheFile, $cacheExpiry) {
    if (!file_exists($cacheFile)) {
        return true;
    }
    
    $cacheContent = file_get_contents($cacheFile);
    $cacheData = json_decode($cacheContent, true);
    
    if (!$cacheData || !isset($cacheData['timestamp'])) {
        return true;
    }
    
    return (time() - $cacheData['timestamp'] > $cacheExpiry);
}

// Main execution
echo "Cache Warmer Started at " . date('Y-m-d H:i:s') . "\n";

if (!file_exists($cacheFile)) {
    echo "Cache Warmer: No cache file exists, creating initial cache...\n";
    warmCache($cacheFile);
} elseif (isCacheExpired($cacheFile, $cacheExpiry)) {
    echo "Cache Warmer: Cache is expired, refreshing...\n";
    warmCache($cacheFile);
} else {
    echo "Cache Warmer: Cache is still fresh, no update needed\n";
}

echo "Cache Warmer Completed at " . date('Y-m-d H:i:s') . "\n";
?>