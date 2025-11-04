<?php
// Load environment variables
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
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

// Load .env file
loadEnv(__DIR__ . '/.env');

// Cache configuration from .env
$cacheDir = __DIR__ . '/' . (getenv('CACHE_DIRECTORY') ?: 'cache');
$cacheFile = $cacheDir . '/facility_data.json';
$cacheExpiry = (int)(getenv('CACHE_EXPIRY') ?: 3600);

// Create cache directory if it doesn't exist
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Cache management functions
function saveToCache($data, $cacheFile) {
    $cacheData = [
        'timestamp' => time(),
        'hospitals' => $data
    ];
    return file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
}

function loadFromCache($cacheFile, $cacheExpiry) {
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheContent = file_get_contents($cacheFile);
    $cacheData = json_decode($cacheContent, true);
    
    if (!$cacheData || !isset($cacheData['timestamp']) || !isset($cacheData['hospitals'])) {
        return null;
    }
    
    return $cacheData['hospitals'];
}

function loadFromCacheAlways($cacheFile) {
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheContent = file_get_contents($cacheFile);
    $cacheData = json_decode($cacheContent, true);
    
    if (!$cacheData || !isset($cacheData['hospitals'])) {
        return null;
    }
    
    return $cacheData['hospitals'];
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

function refreshCacheInBackground($cacheFile, $sheetUrl, $context) {
    $lockFile = $cacheFile . '.lock';
    $logFile = dirname($cacheFile) . '/cache_refresh.log';
    
    // Check if another process is already refreshing
    if (file_exists($lockFile)) {
        // Check if lock is stale (older than 5 minutes)
        if (time() - filemtime($lockFile) > 300) {
            unlink($lockFile);
            error_log(date('Y-m-d H:i:s') . " - Removed stale lock file\n", 3, $logFile);
        } else {
            error_log(date('Y-m-d H:i:s') . " - Another process is already refreshing cache\n", 3, $logFile);
            return false; // Another process is refreshing
        }
    }
    
    // Create lock file
    file_put_contents($lockFile, getmypid());
    error_log(date('Y-m-d H:i:s') . " - Started background cache refresh (PID: " . getmypid() . ")\n", 3, $logFile);
    
    try {
        $csvData = @file_get_contents($sheetUrl, false, $context);
        
        if ($csvData !== FALSE) {
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
            
            // Save the fetched data to cache
            if (!empty($hospitals)) {
                saveToCache($hospitals, $cacheFile);
                error_log(date('Y-m-d H:i:s') . " - Successfully refreshed cache with " . count($hospitals) . " hospitals\n", 3, $logFile);
            } else {
                error_log(date('Y-m-d H:i:s') . " - No hospital data found during refresh\n", 3, $logFile);
            }
        } else {
            error_log(date('Y-m-d H:i:s') . " - Failed to fetch data from Google Sheets during background refresh\n", 3, $logFile);
        }
    } catch (Exception $e) {
        error_log(date('Y-m-d H:i:s') . " - Error during background refresh: " . $e->getMessage() . "\n", 3, $logFile);
    } finally {
        // Remove lock file
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
        error_log(date('Y-m-d H:i:s') . " - Completed background cache refresh\n", 3, $logFile);
    }
    
    return true;
}

// Always try to load from cache first (users always get cached data)
$hospitals = loadFromCacheAlways($cacheFile);

// If no cache exists at all, create initial cache
if ($hospitals === null) {
    // Initialize empty array for fallback
    $hospitals = [];
    
    // Fetch data from Google Sheets for initial cache
    $sheetUrl = getenv('GOOGLE_SHEETS_URL');

    $context = stream_context_create([
        'http' => [
            'timeout' => (int)(getenv('GOOGLE_SHEETS_TIMEOUT') ?: 10),
            'user_agent' => getenv('HTTP_USER_AGENT') ?: getenv('USER_AGENT')
        ]
    ]);

    $csvData = @file_get_contents($sheetUrl, false, $context);

    if ($csvData !== FALSE) {
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
        
        // Save the fetched data to cache
        if (!empty($hospitals)) {
            saveToCache($hospitals, $cacheFile);
        }
    }
} else {
    // Cache exists, check if it needs background refresh
    if (isCacheExpired($cacheFile, $cacheExpiry)) {
        // Trigger background refresh (non-blocking)
        $sheetUrl = getenv('GOOGLE_SHEETS_URL');
        $context = stream_context_create([
            'http' => [
                'timeout' => (int)(getenv('GOOGLE_SHEETS_TIMEOUT') ?: 10),
                'user_agent' => getenv('HTTP_USER_AGENT') ?: getenv('USER_AGENT')
            ]
        ]);
        
        // Start background refresh (this won't block the user)
        refreshCacheInBackground($cacheFile, $sheetUrl, $context);
    }
}

// Get selected hospital
$selectedIndex = isset($_GET['hospital']) ? (int)$_GET['hospital'] : 0;
$hospitalData = $hospitals[$selectedIndex] ?? [];

// Map CSV data to hs.php structure
$hospital = [
    'hospital_id' => $selectedIndex + 1,
    'name' => $hospitalData['PTJ'] ?? 'Hospital Name',
    'short_name' => $hospitalData['PTJ'] ?? '',
    'type' => 'Government',
    'category' => 'Public',
    'status' => 'Active',
    'established_date' => $hospitalData['Tahun Operasi'] ?? '1985',
    'address' => [
        'street' => $hospitalData['Alamat'] ?? '',
        'city' => $hospitalData['Daerah'] ?? '',
        'state' => 'Kedah',
        'postcode' => $hospitalData['Poskod'] ?? '',
        'country' => 'Malaysia'
    ],
    'contact' => [
        'phone' => $hospitalData['no_telefon'] ?? 'N/A',
        'fax' => $hospitalData['No. Faks'] ?? 'N/A',
        'email' => strtolower(str_replace(' ', '', $hospitalData['PTJ'] ?? 'info')) . '@moh.gov.my',
        'website' => $hospitalData['laman_rasmi'] ?? 'https://www.moh.gov.my',
        'emergency' => $hospitalData['no_telefon'] ?? 'N/A'
    ],
    'facilities' => [
        'total_beds' => intval($hospitalData['Jumlah katil'] ?? 0),
        'icu_beds' => rand(10, 50),
        'emergency_beds' => rand(20, 80),
        'operating_rooms' => rand(5, 15),
        'parking_spaces' => rand(100, 500)
    ],
    'statistics' => [
        'annual_admissions' => rand(10000, 50000),
        'bed_occupancy_rate' => floatval(preg_replace('/[^0-9.]/', '', $hospitalData['BOR'] ?? '0')),
        'average_stay_days' => floatval(preg_replace('/[^0-9.]/', '', $hospitalData['ALOS'] ?? '0'))
    ],
    'departments' => [
        'Perubatan Am',
        'Pembedahan Am',
        'Ortopedik',
        'Pediatrik',
        'O&G',
        'Anest',
        'ORL',
        'Oftal',
        'Psy',
        'OMF',
        'Kecemasan',
        'Pergigian Pediatrik'
    ],
    'services' => [
        'Forensik',
        'Anatomical Pathology Microbiology Hematology',
        'Radiology',
        'Transfusi',
        'Dietetik',
        'Fisioterapi',
        'Pemulihan Cara Kerja',
        'Kerja Sosial'
    ],
    'administration' => [
        'director' => strtoupper($hospitalData['Ketua PTJ'] ?? 'N/A'),
        'deputy_director' => $hospitalData['Timbalan Pengarah'] ?? 'N/A',
        'doctors' => rand(20, 100),
        'nurses' => rand(50, 300),
        'support_staff' => rand(30, 150),
        'total_staff' => (intval($hospitalData['Staff Tetap'] ?? 0) + intval($hospitalData['Staff Kontrak'] ?? 0))
    ],
    'cluster' => $hospitalData['Kluster'] ?? '',
    'land_area' => $hospitalData['Keluasan Tapak (Ekar)'] ?? '',
    'updated_at' => date('Y-m-d H:i:s')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['name'] ?? 'Hospital Information'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="assets/js/tailwind-config.js"></script>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-cyan-50 min-h-screen">

<!-- Hospital Selector -->
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 shadow-lg border-b border-blue-100">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex flex-col md:flex-row items-center md:items-center gap-4 md:gap-6">
            <!-- Icon and Label Section -->
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md">
                    <i class="fas fa-hospital-alt text-white text-xl"></i>
                </div>
                <div class="text-center md:text-left">
                    <label class="text-lg font-bold text-gray-800 block">Select Fasiliti</label>
                    <p class="text-sm text-gray-600">Choose a fasiliti to view detailed information</p>
                </div>
            </div>
            
            <!-- Selector Section -->
            <div class="flex-1 flex flex-col md:flex-row items-center md:items-center gap-4">
                <div class="relative flex-1 max-w-2xl">
                    <select onchange="window.location.href='?hospital='+this.value" 
                            class="w-full px-4 py-3 pl-12 pr-10 bg-white border-2 border-blue-200 rounded-xl text-gray-800 font-medium shadow-sm hover:border-blue-300 focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-200 appearance-none cursor-pointer">
                        <?php foreach ($hospitals as $index => $hosp): ?>
                            <option value="<?php echo $index; ?>" <?php echo $index === $selectedIndex ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hosp['PTJ'] ?? "Hospital $index"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Custom dropdown icon -->
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fas fa-search text-blue-400"></i>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <i class="fas fa-chevron-down text-blue-400"></i>
                    </div>
                </div>
                
                <!-- Hospital Count Badge -->
                <div class="flex items-center gap-2 bg-white px-4 py-3 rounded-xl shadow-sm border border-blue-100">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                        <i class="fas fa-chart-bar text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-blue-600"><?php echo count($hospitals); ?></span>
                        <span class="text-sm text-gray-600 ml-1">Fasiliti</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hero Header Section -->
<div class="gradient-bg text-white">
    <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-8 md:gap-12">
            <!-- Hospital Info -->
            <div class="flex-1 md:flex-[2] animate-fade-in-up">
                <div class="inline-flex items-center gap-3 bg-white/20 backdrop-blur-md px-4 py-2 rounded-full text-sm mb-4 border border-white/30">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="font-semibold"><?php echo $hospital['status'] ?? 'Active'; ?></span>
                    <span class="text-white/70">|</span>
                    <span><?php echo $hospital['type'] ?? ''; ?> Hospital</span>
                    <span class="text-white/70">|</span>
                    <span class="bg-yellow-400/20 px-2 py-0.5 rounded text-yellow-100"><?php echo $hospital['category'] ?? ''; ?></span>
                </div>
                
                <h1 class="text-5xl md:text-6xl font-bold mb-4 leading-tight">
                    <?php echo $hospital['name'] ?? 'Hospital Name'; ?>
                </h1>
                
                <div class="flex items-center gap-2 text-lg text-blue-100 mb-3">
                    <i class="fas fa-hospital text-2xl"></i>
                    <span class="font-semibold text-2xl"><?php echo $hospital['short_name'] ?? ''; ?></span>
                </div>
                
                <div class="flex items-center gap-2 text-blue-100 mb-2">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo $hospital['address']['street'] ?? ''; ?>, <?php echo $hospital['address']['city'] ?? ''; ?>, <?php echo $hospital['address']['state'] ?? ''; ?> <?php echo $hospital['address']['postcode'] ?? ''; ?></span>
                </div>
                
                <div class="flex items-center gap-2 text-blue-100 mb-2">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Tahun Operasi : <?php echo $hospital['established_date'] ?? '1985'; ?></span>
                </div>
                
                <?php 
                $clusterData = $hospital['cluster'] ?? '';
                if (!empty($clusterData) && strtoupper($clusterData) !== 'NA'): 
                ?>
                <div class="flex items-center gap-2 text-blue-100 mb-2">
                    <i class="fas fa-layer-group"></i>
                    <span>Kluster : <?php echo $clusterData; ?></span>
                </div>
                <?php endif; ?>

                <?php 
                $landAreaData = $hospital['land_area'] ?? '';
                if (!empty($landAreaData) && strtoupper($landAreaData) !== 'NA'): 
                ?>
                <div class="flex items-center gap-2 text-blue-100">
                    <i class="fas fa-ruler-combined"></i>
                    <span>Keluasan Tanah: <?php echo $landAreaData; ?> Ekar</span>
                </div>
                <?php endif; ?>


            </div>

            <!-- Quick Contact Card -->
            <div class="glass-effect rounded-2xl p-6 w-full max-w-sm mx-auto md:mx-0 md:w-auto md:max-w-[350px] shadow-2xl animate-fade-in-up" style="animation-delay: 0.2s;">
                <h3 class="text-sm uppercase tracking-wider mb-4 text-blue-600 font-bold flex items-center gap-2">
                    <i class="fas fa-phone-volume"></i>
                    Quick Contact
                </h3>
                
                <a href="tel:<?php echo $hospital['contact']['phone'] ?? ''; ?>" class="block mb-4 group">
                    <div class="flex items-center gap-3 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white px-4 py-3 rounded-xl transition-all duration-300 transform group-hover:scale-105">
                        <i class="fas fa-phone-alt text-xl"></i>
                        <div class="flex-1">
                            <p class="text-xs opacity-90">Phone</p>
                            <p class="text-lg font-bold"><?php echo $hospital['contact']['phone'] ?? 'N/A'; ?></p>
                        </div>
                    </div>
                </a>
                
                <a href="<?php echo $hospital['contact']['website'] ?? '#'; ?>" target="_blank" class="block group">
                    <div class="flex items-center gap-3 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-xl transition-all duration-300">
                        <i class="fas fa-globe text-xl text-blue-600"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500">Laman Web Rasmi</p>
                            <p class="text-sm font-semibold truncate"><?php 
                                $website = $hospital['contact']['website'] ?? '';
                                if (!empty($website)) {
                                    // If it's a full URL, extract the host and path
                                    if (filter_var($website, FILTER_VALIDATE_URL)) {
                                        $parsed = parse_url($website);
                                        echo ($parsed['host'] ?? '') . ($parsed['path'] ?? '');
                                    } else {
                                        // If it's not a full URL, display as is (e.g., jknkedah.moh.gov.my/hsm)
                                        echo $website;
                                    }
                                } else {
                                    echo 'www.moh.gov.my';
                                }
                            ?></p>
                        </div>
                        <i class="fas fa-external-link-alt text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-10">
    
    <!-- Statistics Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Total Beds -->
        <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-blue-500 animate-fade-in-up">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 rounded-2xl p-4">
                    <i class="fas fa-bed text-blue-600 text-3xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Beds</p>
                    <h3 class="text-4xl font-bold stat-number">
                        <?php echo number_format($hospital['facilities']['total_beds'] ?? 0); ?>
                    </h3>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-user-md mr-1"></i> Total Beds 
            </div>
        </div>

        <!-- Total Staff -->
        <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-green-500 animate-fade-in-up" style="animation-delay: 0.1s;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 rounded-2xl p-4">
                    <i class="fas fa-users text-green-600 text-3xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Total Staff</p>
                    <h3 class="text-4xl font-bold text-green-600">
                        <?php echo number_format($hospital['administration']['total_staff'] ?? 0); ?>
                    </h3>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-user-md mr-1"></i> Healthcare professionals on duty
            </div>
        </div>

        <!-- Bed Occupancy Rate -->
        <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-purple-500 animate-fade-in-up" style="animation-delay: 0.2s;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 rounded-2xl p-4">
                    <i class="fas fa-user-injured text-purple-600 text-3xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">BOR</p>
                    <h3 class="text-4xl font-bold text-purple-600">
                        <?php echo number_format($hospital['statistics']['bed_occupancy_rate'] ?? 0, 2); ?>%
                    </h3>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-chart-line mr-1"></i> Bed Occupancy Rate
            </div>
        </div>

        <!-- Average Length of Stay -->
        <div class="bg-white rounded-2xl shadow-lg p-6 card-hover border-l-4 border-orange-500 animate-fade-in-up" style="animation-delay: 0.3s;">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-orange-100 rounded-2xl p-4">
                    <i class="fas fa-percentage text-orange-600 text-3xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider">ALOS</p>
                    <h3 class="text-4xl font-bold text-orange-600">
                        <?php echo $hospital['statistics']['average_stay_days'] ?? 0; ?> %
                    </h3>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                <i class="fas fa-chart-line mr-1"></i> Average Length of Stay
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Sidebar -->
        <div class="lg:col-span-1 space-y-6">

            <!-- Administration Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <div class="bg-pink-100 rounded-lg p-2">
                        <i class="fas fa-user-tie text-pink-600"></i>
                    </div>
                    Administration
                </h2>
                
                <div class="mb-5 p-4 bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl border border-pink-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">Ketua PTJ</p>
                    <p class="text-lg font-bold text-gray-800"><?php echo $hospital['administration']['director'] ?? 'N/A'; ?></p>
                </div>

                <div class="mb-5 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl border border-blue-100">
                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">Timbalan Ketua PTJ</p>
                    <div class="text-sm font-bold text-gray-800 text-base"><?php 
                        $deputyDirector = $hospital['administration']['deputy_director'] ?? 'N/A';
                        if ($deputyDirector !== 'N/A' && !empty(trim($deputyDirector))) {
                            // Convert line breaks to HTML <br> tags
                            echo nl2br(htmlspecialchars($deputyDirector, ENT_QUOTES, 'UTF-8'));
                        } else {
                            echo 'N/A';
                        }
                    ?></div>
                </div>
            </div>

            <!-- Staff Information Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <div class="bg-emerald-100 rounded-lg p-2">
                        <i class="fas fa-users text-emerald-600"></i>
                    </div>
                    Maklumat Kakitangan
                </h2>
                <div class="space-y-4">
                    <?php 
                    $staffCategories = [
                        ['name' => 'Doktor', 'permanent' => $hospitalData['Doktor'] ?? '', 'contract' => $hospitalData['DRC'] ?? '', 'icon' => 'fa-user-md', 'color' => 'blue'],
                        ['name' => 'Jururawat', 'permanent' => $hospitalData['Jururawat'] ?? '', 'contract' => $hospitalData['JRC'] ?? '', 'icon' => 'fa-user-nurse', 'color' => 'pink'],
                        ['name' => 'Penolog Pegawai Perubatan', 'permanent' => $hospitalData['PPP'] ?? '', 'contract' => $hospitalData['PPPC'] ?? '', 'icon' => 'fa-stethoscope', 'color' => 'purple'],
                        ['name' => 'Radiologi', 'permanent' => $hospitalData['Radiologi'] ?? '', 'contract' => $hospitalData['RC'] ?? '', 'icon' => 'fa-x-ray', 'color' => 'indigo'],
                        ['name' => 'Fisioterapi', 'permanent' => $hospitalData['Fisoterapi'] ?? '', 'contract' => $hospitalData['FC'] ?? '', 'icon' => 'fa-dumbbell', 'color' => 'green'],
                        ['name' => 'Lain-lain', 'permanent' => $hospitalData['Lain-lain'] ?? '', 'contract' => $hospitalData['LC'] ?? '', 'icon' => 'fa-user-friends', 'color' => 'gray']
                    ];
                    $initialCategories = array_slice($staffCategories, 0, 3);
                    $moreCategories = array_slice($staffCategories, 3);

                    // Render first 3 categories
                    foreach ($initialCategories as $category) {
                        $permanent = !empty(trim($category['permanent'])) ? $category['permanent'] : '0';
                        $contract = !empty(trim($category['contract'])) ? $category['contract'] : '0';
                        $color = $category['color'];
                        ?>
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-<?= $color ?>-50 to-<?= $color ?>-100 rounded-lg border border-<?= $color ?>-200">
                            <div class="flex items-center gap-3">
                                <div class="bg-<?= $color ?>-200 rounded-lg p-2">
                                    <i class="fas <?= $category['icon'] ?> text-<?= $color ?>-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-gray-800 text-sm"><?= $category['name'] ?></span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <div class="text-center">
                                    <div class="font-bold text-<?= $color ?>-700"><?= htmlspecialchars($permanent) ?></div>
                                    <div class="text-xs text-gray-500">Tetap</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-bold text-<?= $color ?>-600"><?= htmlspecialchars($contract) ?></div>
                                    <div class="text-xs text-gray-500">Kontrak</div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }

                    // Render the rest hidden by default with a toggle
                    if (!empty($moreCategories)) {
                        ?>
                        <div id="more-staff-categories" class="space-y-4 collapsible">
                            <?php foreach ($moreCategories as $category) {
                                $permanent = !empty(trim($category['permanent'])) ? $category['permanent'] : '0';
                                $contract = !empty(trim($category['contract'])) ? $category['contract'] : '0';
                                $color = $category['color'];
                                ?>
                                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-<?= $color ?>-50 to-<?= $color ?>-100 rounded-lg border border-<?= $color ?>-200">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-<?= $color ?>-200 rounded-lg p-2">
                                            <i class="fas <?= $category['icon'] ?> text-<?= $color ?>-600 text-sm"></i>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm"><?= $category['name'] ?></span>
                                    </div>
                                    <div class="flex gap-4 text-sm">
                                        <div class="text-center">
                                            <div class="font-bold text-<?= $color ?>-700"><?= htmlspecialchars($permanent) ?></div>
                                            <div class="text-xs text-gray-500">Tetap</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="font-bold text-<?= $color ?>-600"><?= htmlspecialchars($contract) ?></div>
                                            <div class="text-xs text-gray-500">Kontrak</div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            } ?>
                        </div>
                        <div class="mt-3 flex justify-center">
                            <button id="toggleStaffCategories" type="button" class="text-sm px-3 py-2 rounded-md bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition">
                                See more
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Facilities Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <div class="bg-cyan-100 rounded-lg p-2">
                        <i class="fas fa-hospital-symbol text-cyan-600"></i>
                    </div>
                    Fasiliti
                </h2>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $facilities = $hospitalData['Fasiliti/Kemudahan (CTH : Surau, parking dll)'] ?? '';
                    $colors = [
                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                        'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300'
                    ];

                    if ($facilities) {
                        foreach (array_filter(array_map('trim', explode("\n", $facilities))) as $i => $facility) {
                            $colorClass = $colors[$i % count($colors)];
                            ?>
                            <span class="<?= $colorClass ?> text-xs font-medium px-2.5 py-0.5 rounded-full">
                                <?= htmlspecialchars($facility) ?>
                            </span>
                            <?php
                        }
                    } else { ?>
                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">
                            No facilities data available
                        </span>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Perkhidmatan Kepakaran Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <div class="bg-indigo-100 rounded-lg p-2">
                        <i class="fas fa-building text-indigo-600"></i>
                    </div>
                    Perkhidmatan Kepakaran
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php 
                    // Only show departments that have "Ada" status
                    $departmentFields = [
                        'Perubatan Am', 'Pembedahan Am', 'Ortopedik', 'Pediatrik', 'O&G', 
                        'Anest', 'ORL', 'Oftal', 'Psy', 'OMF', 'Kecemasan', 'Pergigian Pediatrik'
                    ];
                    
                    $availableDepartments = [];
                    foreach ($departmentFields as $field) {
                        if (isset($hospitalData[$field]) && strtolower(trim($hospitalData[$field])) === 'ada') {
                            $availableDepartments[] = $field;
                        }
                    }
                    
                    // Check for Lain (Sila Nyatakan) field in departments
                    $lainDeptContent = '';
                    if (isset($hospitalData['Lain (Sila Nyatakan)']) && !empty(trim($hospitalData['Lain (Sila Nyatakan)']))) {
                        $lainDeptContent = trim($hospitalData['Lain (Sila Nyatakan)']);
                    }
                    
                    // Check if we have any departments or Lain content to display
                    if (!empty($availableDepartments) || !empty($lainDeptContent)): 
                        // Display available departments
                        if (!empty($availableDepartments)):
                            foreach ($availableDepartments as $dept): 
                    ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-indigo-50 hover:border-indigo-200 border border-transparent transition-all duration-300">
                        <div class="bg-indigo-100 rounded-lg p-2">
                            <i class="fas fa-check text-indigo-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo $dept; ?></span>
                    </div>
                    <?php 
                            endforeach;
                        endif;
                        
                        // Display Lain (Sila Nyatakan) only if there's content
                        if (!empty($lainDeptContent)): 
                    ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-purple-50 hover:border-purple-200 border border-transparent transition-all duration-300 cursor-pointer" onclick="openLainDeptModal()">
                        <div class="bg-purple-100 rounded-lg p-2">
                            <i class="fas fa-plus-circle text-purple-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Lain (Sila Nyatakan)</span>
                        <div class="ml-auto">
                            <i class="fas fa-external-link-alt text-purple-500 text-xs"></i>
                        </div>
                    </div>
                    <?php 
                        endif;
                    else: 
                        // Show message only when there are no departments AND no Lain content
                    ?>
                    <div class="col-span-full text-center py-8">
                        <div class="bg-gray-100 rounded-lg p-4 inline-block">
                            <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500 font-medium">Tiada maklumat perkhidmatan kepakaran tersedia</p>
                        </div>
                    </div>
                    <?php 
                    endif; ?>
                </div>
            </div>

            <!-- Sokongan Klinikal Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
                    <div class="bg-teal-100 rounded-lg p-2">
                        <i class="fas fa-stethoscope text-teal-600"></i>
                    </div>
                    Sokongan Klinikal
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php 
                    // Only show services that have "Ada" status
                    $serviceFields = [
                        'Forensik', 'Anatomical Pathology Microbiology Hematology', 'Radiology', 'Transfusi', 
                        'Farmasi', 'Dietetik', 'Fisioterapi', 'Pemulihan cara Kerja', 'Kerja Sosial'
                    ];
                    
                    $availableServices = [];
                    foreach ($serviceFields as $field) {
                        if (isset($hospitalData[$field]) && strtolower(trim($hospitalData[$field])) === 'ada') {
                            $availableServices[] = $field;
                        }
                    }
                    
                    // Check for Lain-Lain (Sila Nyatakan) field
                    $lainLainContent = '';
                    if (isset($hospitalData['Lain-Lain (Sila Nyatakan']) && !empty(trim($hospitalData['Lain-Lain (Sila Nyatakan']))) {
                        $lainLainContent = trim($hospitalData['Lain-Lain (Sila Nyatakan']);
                    }
                    
                    // Check if we have any services or Lain-Lain content to display
                    if (!empty($availableServices) || !empty($lainLainContent)): 
                        // Display available services
                        if (!empty($availableServices)):
                            foreach ($availableServices as $service): 
                    ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-teal-50 hover:border-teal-200 border border-transparent transition-all duration-300">
                        <div class="bg-teal-100 rounded-lg p-2">
                            <i class="fas fa-heartbeat text-teal-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo $service; ?></span>
                    </div>
                    <?php 
                            endforeach;
                        endif;
                        
                        // Display Lain-Lain only if there's content
                        if (!empty($lainLainContent)): 
                    ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-blue-50 hover:border-blue-200 border border-transparent transition-all duration-300 cursor-pointer" onclick="openLainLainModal()">
                        <div class="bg-blue-100 rounded-lg p-2">
                            <i class="fas fa-plus-circle text-blue-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Lain-Lain Sokongan Klinikal</span>
                        <div class="ml-auto">
                            <i class="fas fa-external-link-alt text-blue-500 text-xs"></i>
                        </div>
                    </div>
                    <?php 
                        endif;
                    else: 
                        // Show message only when there are no services AND no Lain-Lain content
                    ?>
                    <div class="col-span-full text-center py-8">
                        <div class="bg-gray-100 rounded-lg p-4 inline-block">
                            <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500 font-medium">Tiada maklumat sokongan klinikal tersedia</p>
                        </div>
                    </div>
                    <?php 
                    endif; 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-10 bg-white rounded-2xl shadow-lg p-6 text-center">
        <div class="flex items-center justify-center gap-4 flex-wrap">
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-calendar text-blue-600"></i>
                <span class="text-sm">Last Updated: <span class="font-semibold"><?php echo date('F d, Y \a\t H:i', strtotime($hospital['updated_at'] ?? 'now')); ?></span></span>
            </div>
            <span class="text-gray-300">|</span>
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-shield-alt text-green-600"></i>
                <span class="text-sm">Hospital ID: <span class="font-semibold"><?php echo $hospital['hospital_id'] ?? 'N/A'; ?></span></span>
            </div>
        </div>
    </div>

</div>

<!-- Lain-Lain Modal -->
<div id="lainLainModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-lg p-2">
                        <i class="fas fa-plus-circle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Lain-Lain Sokongan Klinikal</h3>
                        <p class="text-blue-100 text-sm">Perkhidmatan Tambahan</p>
                    </div>
                </div>
                <button onclick="closeLainLainModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all duration-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-[60vh]">
            <div id="lainLainContent" class="text-gray-700 leading-relaxed whitespace-pre-line">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Lain Dept Modal -->
<div id="lainDeptModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-white bg-opacity-20 rounded-lg p-2">
                        <i class="fas fa-plus-circle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Lain Perkhidmatan Kepakaran</h3>
                        <p class="text-purple-100 text-sm">Jabatan Tambahan</p>
                    </div>
                </div>
                <button onclick="closeLainDeptModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all duration-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto max-h-[60vh]">
            <div id="lainDeptContent" class="text-gray-700 leading-relaxed whitespace-pre-line">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
// Store the Lain-Lain content for the modal
window.lainLainData = <?php echo json_encode($lainLainContent ?? ''); ?>;
// Store the Lain Dept content for the modal
window.lainDeptData = <?php echo json_encode($lainDeptContent ?? ''); ?>;
</script>
<script src="assets/js/main.js"></script>

</body>
</html>
