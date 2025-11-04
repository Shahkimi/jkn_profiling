<?php
// Enhanced Google Sheets → CSV Converter
// Modern single-screen UI with no scrolling

declare(strict_types=1);
session_start();

// Polyfills for PHP 7.4 (PHP 8 provides these natively)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle): bool {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle): bool {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        $n = strlen($needle);
        if ($n > strlen($haystack)) return false;
        return substr($haystack, -$n) === $needle;
    }
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; script-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data:; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none';");

// ----- Utilities -----
function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function validate_csrf(?string $token): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($sessionToken, $token);
}

function sanitize_url(string $url): string {
    $url = trim($url, " \t\n\r\0\x0B\"'`");
    $url = filter_var($url, FILTER_SANITIZE_URL) ?: '';
    
    // Normalize edit URLs to CSV export, prioritizing query gid over fragment gid
    $variations = [
        '/\/edit.*gid=(\d+).*$/i' => '/export?format=csv&gid=$1', // query param takes precedence
        '/\/edit.*#gid=(\d+).*$/i' => '/export?format=csv&gid=$1', // fragment fallback
        '/\/edit.*$/i' => '/export?format=csv&gid=0' // default if no gid present
    ];
    
    foreach ($variations as $pattern => $replacement) {
        if (preg_match($pattern, $url)) {
            $url = preg_replace($pattern, $replacement, $url);
            break;
        }
    }
    
    return $url;
}

function validate_google_sheets_url(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    
    $parsed = parse_url($url);
    if (!$parsed || ($parsed['host'] ?? '') !== 'docs.google.com') return false;
    
    return preg_match('#^/spreadsheets/d/([a-zA-Z0-9-_]+)#', $parsed['path'] ?? '') === 1;
}

function convert_to_csv_url(string $url): string {
    if (!validate_google_sheets_url($url)) return '';
    
    $url = sanitize_url($url);
    if (strpos($url, '/export?') === false) {
        $url = preg_replace('#(/spreadsheets/d/[^/]+).*#', '$1/export?format=csv&gid=0', $url);
    }
    
    return $url;
}

function extract_sheet_id(string $url): string {
    if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $matches)) {
        return $matches[1];
    }
    return '';
}

function get_sheet_title(string $url): string {
    $csvUrl = convert_to_csv_url($url);
    if (!$csvUrl) return 'Unknown Sheet';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; CSV-Converter/1.0)',
            'method' => 'HEAD'
        ]
    ]);
    
    $headers = @get_headers($csvUrl, 1, $context);
    if (is_array($headers) && isset($headers['Content-Disposition'])) {
        $disposition = is_array($headers['Content-Disposition']) 
            ? end($headers['Content-Disposition']) 
            : $headers['Content-Disposition'];
        
        if (preg_match('/filename\*?=(?:UTF-8\'\')?["\']?([^"\';\\r\\n]+)["\']?/i', $disposition, $matches)) {
            return urldecode($matches[1]);
        }
    }
    
    return 'Google Sheet - ' . date('Y-m-d');
}

// ----- Main Logic -----
$error = '';
$success = '';
$csvUrl = '';
$submittedUrl = '';
$csrf = csrf_token();
$sheetTitle = '';
$currentStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $url = trim($_POST['sheet_url'] ?? '');
    
    if (!validate_csrf($token)) {
        $error = 'Security token mismatch. Please try again.';
        $currentStep = 1;
    } elseif (empty($url)) {
        $error = 'Please enter a Google Sheets URL.';
        $currentStep = 1;
    } elseif (!validate_google_sheets_url($url)) {
        $error = 'Please enter a valid Google Sheets URL (must be from docs.google.com/spreadsheets).';
        $currentStep = 1;
    } else {
        $submittedUrl = $url;
        $csvUrl = convert_to_csv_url($url);
        
        if ($csvUrl) {
            $sheetTitle = get_sheet_title($url);
            $success = 'CSV URL generated successfully!';
            $currentStep = 3;
            $_SESSION['csv_data'] = [
                'url' => $csvUrl,
                'title' => $sheetTitle,
                'original' => $submittedUrl,
                'timestamp' => time()
            ];
        } else {
            $error = 'Failed to generate CSV URL. Please check the sheet URL and try again.';
            $currentStep = 1;
        }
    }
}

// Handle download action
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_SESSION['csv_data'])) {
    $csvData = $_SESSION['csv_data'];
    $csvUrl = $csvData['url'] ?? '';
    $filename = $csvData['title'] ?? 'sheet';
    
    if ($csvUrl) {
        $filename = preg_replace('/[^\w\-_\.]/', '_', $filename);
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; CSV-Converter/1.0)'
            ]
        ]);
        
        $csvContent = @file_get_contents($csvUrl, false, $context);
        
        if ($csvContent !== false) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . strlen($csvContent));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            echo $csvContent;
            exit;
        } else {
            $error = 'Failed to fetch CSV data. The sheet might be private or the URL might be invalid.';
            $currentStep = 1;
        }
    }
}

// Reset to new conversion
if (isset($_GET['action']) && $_GET['action'] === 'new') {
    unset($_SESSION['csv_data']);
    $error = '';
    $success = '';
    $csvUrl = '';
    $submittedUrl = '';
    $sheetTitle = '';
    $currentStep = 1;
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Google Sheets to CSV Converter</title>
  <meta name="description" content="Convert Google Sheets to CSV instantly. Modern UI with no scrolling required.">
  
  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
            }
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', 'sans-serif']
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-out',
            'slide-up': 'slideUp 0.3s ease-out',
            'scale-in': 'scaleIn 0.2s ease-out'
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(10px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            slideUp: {
              '0%': { opacity: '0', transform: 'translateY(20px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            scaleIn: {
              '0%': { opacity: '0', transform: 'scale(0.95)' },
              '100%': { opacity: '1', transform: 'scale(1)' }
            }
          }
        }
      }
    }
  </script>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      height: 100vh;
      overflow: hidden;
      font-family: 'Inter', system-ui, sans-serif;
    }
    
    .glass {
      background: rgba(255, 255, 255, 0.25);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .dark .glass {
      background: rgba(15, 23, 42, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .input-focus {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .input-focus:focus {
      transform: translateY(-1px);
      box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
      transform: translateY(-1px);
      box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
    }
    
    .btn-secondary {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-secondary:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px -5px rgba(0, 0, 0, 0.3);
    }
    
    .progress-step {
      transition: all 0.3s ease-in-out;
    }
    
    .toast {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 1000;
      transform: translateX(100%);
      transition: transform 0.3s ease-in-out;
    }
    
    .toast.show {
      transform: translateX(0);
    }
    
    @media (max-width: 640px) {
      .glass {
        margin: 1rem;
        padding: 1.5rem;
      }
    }
  </style>
</head>

<body class="h-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-indigo-900 text-slate-800 dark:text-slate-200 transition-colors duration-300">
  
  <!-- Background Decorations -->
  <div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-20 -left-20 w-72 h-72 bg-blue-300/20 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-purple-300/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-indigo-300/10 rounded-full blur-2xl animate-pulse" style="animation-delay: 4s;"></div>
  </div>

  <!-- Dark Mode Toggle -->
  <button onclick="toggleDarkMode()" class="fixed top-4 right-4 z-50 p-3 rounded-full glass hover:bg-white/30 dark:hover:bg-slate-800/30 transition-all duration-300 group">
    <i class="fas fa-moon dark:hidden text-slate-600 group-hover:text-slate-800 transition-colors"></i>
    <i class="fas fa-sun hidden dark:inline text-yellow-400 group-hover:text-yellow-300 transition-colors"></i>
  </button>

  <!-- Toast Notification -->
  <div id="toast" class="toast bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
    <span id="toastMessage">Success!</span>
  </div>

  <!-- Main Container -->
  <div class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      
      <!-- Main Card -->
      <div class="glass rounded-2xl p-8 animate-fade-in">
        
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4 shadow-lg">
            <i class="fas fa-file-csv text-white text-2xl"></i>
          </div>
          <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
            Sheets to CSV
          </h1>
          <p class="text-slate-600 dark:text-slate-400 text-sm">
            Convert Google Sheets to CSV instantly
          </p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-between mb-8 px-4">
          <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="flex items-center <?= $i < 3 ? 'flex-1' : '' ?>">
              <div class="progress-step flex items-center justify-center w-8 h-8 rounded-full <?= $currentStep >= $i ? 'bg-primary-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' ?> text-sm font-semibold">
                <?= $currentStep > $i ? '<i class="fas fa-check text-xs"></i>' : $i ?>
              </div>
              <?php if ($i < 3): ?>
                <div class="flex-1 h-0.5 mx-3 <?= $currentStep > $i ? 'bg-primary-500' : 'bg-slate-200 dark:bg-slate-700' ?> progress-step"></div>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>

        <!-- Content -->
        <div class="space-y-6">
          
          <!-- Error Message -->
          <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 animate-slide-up">
              <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-700 dark:text-red-400 text-sm"><?= htmlspecialchars($error) ?></span>
              </div>
            </div>
          <?php endif; ?>

          <!-- Success Message -->
          <?php if ($success): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 animate-slide-up">
              <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-700 dark:text-green-400 text-sm"><?= htmlspecialchars($success) ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($currentStep <= 2): ?>
            <!-- Input Form -->
            <form method="POST" id="convertForm" class="space-y-4">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              
              <div>
                <label for="sheet_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                  <i class="fas fa-link mr-2"></i>Google Sheets URL
                </label>
                <input 
                  type="url" 
                  id="sheet_url" 
                  name="sheet_url" 
                  value="<?= htmlspecialchars($submittedUrl) ?>"
                  placeholder="https://docs.google.com/spreadsheets/d/..."
                  class="input-focus w-full px-4 py-3 bg-white/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-slate-700 dark:text-slate-200 placeholder-slate-400"
                  required
                  autocomplete="off"
                >
              </div>
              
              <button 
                type="submit" 
                class="btn-primary w-full py-3 px-6 text-white font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                id="convertBtn"
              >
                <i class="fas fa-magic mr-2"></i>Convert to CSV
              </button>
            </form>
          
          <?php else: ?>
            <!-- Success State -->
            <div class="space-y-4 animate-scale-in">
              
              <!-- Sheet Info -->
              <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4">
                <h3 class="font-semibold text-slate-700 dark:text-slate-300 mb-2 flex items-center">
                  <i class="fas fa-file-alt mr-2 text-primary-500"></i>Sheet Details
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 break-all">
                  <strong>Title:</strong> <?= htmlspecialchars($sheetTitle) ?>
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 break-all mt-1">
                  <strong>Source:</strong> <?= htmlspecialchars($submittedUrl) ?>
                </p>
              </div>

              <!-- Action Buttons -->
              <div class="grid grid-cols-1 gap-3">
                <a 
                  href="?action=download" 
                  class="btn-primary inline-flex items-center justify-center py-3 px-6 text-white font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 text-center"
                >
                  <i class="fas fa-download mr-2"></i>Download CSV
                </a>
                
                <button 
                  onclick="copyUrl('<?= htmlspecialchars($csvUrl, ENT_QUOTES) ?>')"
                  class="btn-secondary bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 py-3 px-6 font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 hover:bg-slate-300 dark:hover:bg-slate-600"
                >
                  <i class="fas fa-copy mr-2"></i>Copy CSV URL
                </button>
                
                <a 
                  href="?action=new" 
                  class="btn-secondary bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-3 px-6 font-semibold rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-center"
                >
                  <i class="fas fa-plus mr-2"></i>Convert Another
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
          <p class="text-xs text-slate-500 dark:text-slate-400">
            <i class="fas fa-shield-alt mr-1"></i>Privacy-focused • No data stored • Open source
          </p>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Dark mode functionality
    function toggleDarkMode() {
      document.documentElement.classList.toggle('dark');
      localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
    }

    // Initialize dark mode
    function initDarkMode() {
      const savedMode = localStorage.getItem('darkMode');
      if (savedMode === 'true' || (!savedMode && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
      }
    }

    // Toast notification
    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toastMessage');
      
      toastMessage.textContent = message;
      toast.className = `toast ${type === 'error' ? 'bg-red-500' : 'bg-green-500'} text-white px-6 py-3 rounded-lg shadow-lg show`;
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3000);
    }

    // Copy URL functionality
    function copyUrl(url) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
          showToast('CSV URL copied to clipboard!');
        }).catch(() => {
          fallbackCopy(url);
        });
      } else {
        fallbackCopy(url);
      }
    }

    function fallbackCopy(text) {
      const textArea = document.createElement('textarea');
      textArea.value = text;
      textArea.style.position = 'fixed';
      textArea.style.left = '-9999px';
      document.body.appendChild(textArea);
      textArea.select();
      
      try {
        document.execCommand('copy');
        showToast('CSV URL copied to clipboard!');
      } catch (err) {
        showToast('Failed to copy URL', 'error');
      }
      
      document.body.removeChild(textArea);
    }

    // Form enhancements
    document.addEventListener('DOMContentLoaded', () => {
      initDarkMode();
      
      const form = document.getElementById('convertForm');
      const submitBtn = document.getElementById('convertBtn');
      const urlInput = document.getElementById('sheet_url');
      
      // Auto-focus input if not in success state
      if (urlInput && !<?= $success ? 'true' : 'false' ?>) {
        setTimeout(() => urlInput.focus(), 300);
      }
      
      // Form submission handling
      if (form) {
        form.addEventListener('submit', (e) => {
          if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Converting...';
            submitBtn.disabled = true;
          }
        });
      }
      
      // URL input validation
      if (urlInput) {
        urlInput.addEventListener('input', (e) => {
          const url = e.target.value;
          const isValid = url === '' || url.includes('docs.google.com/spreadsheets');
          
          e.target.style.borderColor = url && !isValid ? '#ef4444' : '';
        });
      }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      // Ctrl/Cmd + Enter to submit form
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const form = document.getElementById('convertForm');
        if (form) form.requestSubmit();
      }
      
      // Escape to clear input
      if (e.key === 'Escape') {
        const urlInput = document.getElementById('sheet_url');
        if (urlInput && urlInput.value) {
          urlInput.value = '';
          urlInput.focus();
        }
      }
    });
  </script>
</body>
</html>
