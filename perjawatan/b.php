<?php
header('Content-Type: application/json');

try {
    // URL for CSV export of your Google Sheets (replace gid if needed)
    $sheetUrl = "https://docs.google.com/spreadsheets/d/1qhDBYxcDzGT7_3LvLNqHPTW_sA0HcAiSBnDquoHhYgM/export?format=csv&gid=1060536213";

    // Context with timeout and user-agent for fetching
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    // Fetch CSV data from Google Sheets
    $csvData = @file_get_contents($sheetUrl, false, $context);

    if ($csvData === FALSE) {
        throw new Exception("Failed to fetch data. Check if spreadsheet is publicly accessible.");
    }

    // Check if we accidentally received HTML (likely permission error page)
    if (stripos($csvData, '<!DOCTYPE html>') !== false || stripos($csvData, '<html') !== false) {
        throw new Exception("Received HTML instead of CSV. Ensure the spreadsheet sharing is set to 'Anyone with the link'.");
    }

    // Save CSV data temporarily for parsing with fgetcsv to handle quoted fields correctly
    $tempFile = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($tempFile, $csvData);

    $handle = fopen($tempFile, 'r');
    if (!$handle) {
        throw new Exception("Failed to open temporary CSV file.");
    }

    $allRows = [];
    while (($row = fgetcsv($handle, 10000, ",")) !== false) {
        $allRows[] = $row;
    }
    fclose($handle);
    unlink($tempFile);

    if (empty($allRows)) {
        throw new Exception("No rows found in CSV file.");
    }

    // Find the header row that contains column "PTJ" (adjust if header name changes)
    $headerIndex = -1;
    $headers = null;
    foreach ($allRows as $index => $row) {
        if (isset($row[0]) && trim($row[0]) === 'Bil') {
            $headerIndex = $index;
            $headers = array_map('trim', $row);
            break;
        }
    }

    if ($headerIndex === -1 || !$headers) {
        throw new Exception("Could not find header row with 'PTJ' column.");
    }

    // Extract data rows after the header
    $dataRows = array_slice($allRows, $headerIndex + 1);

    // Process and clean data rows
    $hospitals = [];
    foreach ($dataRows as $row) {
        // Skip rows that are all empty
        if (empty(array_filter($row, fn($val) => !empty(trim($val ?? ''))))) {
            continue;
        }

        // Skip rows without hospital name (first column empty)
        if (!isset($row[0]) || empty(trim($row[0]))) {
            continue;
        }

        // Pad or trim row to match header column count
        while (count($row) < count($headers)) {
            $row[] = '';
        }
        $row = array_slice($row, 0, count($headers));

        // Clean each value: trim and replace internal CR/LF with spaces
        $cleanRow = array_map(function ($val) {
            $val = trim($val ?? '');
            $val = preg_replace('/\s*[\r\n]+\s*/', ' ', $val);
            return $val;
        }, $row);

        // Combine header and row into associative array
        $hospitals[] = array_combine($headers, $cleanRow);
    }

    if (empty($hospitals)) {
        throw new Exception("No valid hospital data found. Found " . count($dataRows) . " rows after headers.");
    }

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => 'Data retrieved successfully',
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => [
            'total_records' => count($hospitals),
            'perjawatan' => $hospitals
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => null
    ], JSON_PRETTY_PRINT);
}
?>
