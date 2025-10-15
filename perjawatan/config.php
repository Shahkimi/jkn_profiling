<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Load JSON configuration
function loadJsonConfig($dataSource) {
    $configFile = __DIR__ . "/config/{$dataSource}.json";
    
    if (!file_exists($configFile)) {
        throw new Exception("Configuration file not found for data source: {$dataSource}");
    }
    
    $jsonContent = file_get_contents($configFile);
    $config = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in configuration file: " . json_last_error_msg());
    }
    
    return $config;
}

// Get data source from request
$dataSource = $_GET['source'] ?? 'hospital';

try {
    // Validate data source
    $validSources = ['hospital', 'pkd', 'pkpd'];
    if (!in_array($dataSource, $validSources)) {
        throw new Exception('Invalid data source. Valid sources: ' . implode(', ', $validSources));
    }
    
    // Load configuration from JSON file
    $config = loadJsonConfig($dataSource);
    
    // Prepare response data
    $responseData = [
        'ptj_options' => $config['ptj_options'],
        'category_field_map' => []
    ];
    
    // Handle multiple sheets URLs or single sheet URL
    if (isset($config['sheets_urls']) && is_array($config['sheets_urls'])) {
        // Multiple spreadsheet sources
        $responseData['sheets_urls'] = $config['sheets_urls'];
        $responseData['has_multiple_sources'] = true;
    } else {
        // Single spreadsheet source (backward compatibility)
        $responseData['sheets_url'] = $config['sheets_url'];
        $responseData['has_multiple_sources'] = false;
    }
    
    // Build category field map from ptj_options
    foreach ($config['ptj_options'] as $option) {
        $responseData['category_field_map'][$option['value']] = $option['field_key'];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>