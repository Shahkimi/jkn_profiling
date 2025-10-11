<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Data Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- html2canvas for screenshot functionality -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        },
                        accent: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'pulse-soft': 'pulseSoft 2s infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes pulseSoft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-accent-50 via-primary-50 to-accent-100 min-h-screen font-inter">
    <!-- Header Section -->
    <div class="bg-white/80 backdrop-blur-sm border-b border-accent-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-accent-900">Hospital Data Analytics</h1>
                        <p class="text-sm text-accent-600">Real-time visualization dashboard</p>
                    </div>
                </div>
                <div class="hidden sm:flex items-center space-x-2 text-sm text-accent-500">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse-soft"></div>
                    <span>Live Data</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Control Panel -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-accent-200/50 p-6 sm:p-8 mb-8 animate-fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-accent-900 mb-2">Data Categories</h2>
                    <p class="text-accent-600">Select a category to visualize hospital data distribution</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-700">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        13 Categories Available
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <label for="dataSelect" class="block text-sm font-semibold text-accent-700">Pilih PTJ</label>
                <div class="relative">
                    <select id="dataSelect" onchange="loadChart()" class="w-full p-4 pr-12 border-2 border-accent-200 rounded-xl text-base bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300 appearance-none cursor-pointer hover:border-accent-300">
                        <option value="">Pilih PTJ...</option>
                        <option value="PEJ TPKN (KA) JKN">🏥 PEJ TPKN (KA) JKN</option>
                        <option value="PKD KOTA STAR">⭐ PKD KOTA STAR</option>
                        <option value="PKD KUALA MUDA">🌊 PKD KUALA MUDA</option>
                        <option value="PKD KULIM">🏔️ PKD KULIM</option>
                        <option value="PKD KUBANG PASU">🌾 PKD KUBANG PASU</option>
                        <option value="PKD BALING">🏞️ PKD BALING</option>
                        <option value="PKD LANGKAWI">🏝️ PKD LANGKAWI</option>
                        <option value="PKD YAN">🌸 PKD YAN</option>
                        <option value="PKD PENDANG">🌿 PKD PENDANG</option>
                        <option value="PKD SIK">🌲 PKD SIK</option>
                        <option value="PKD BANDAR BAHARU">🏙️ PKD BANDAR BAHARU</option>
                        <option value="VECTOR NEGERI">📊 VECTOR NEGERI</option>
                        <option value="PKPMA BUKIT KAYU HITAM">⛰️ PKPMA BUKIT KAYU HITAM</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <svg class="w-5 h-5 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Catatan Filter Section -->
            <div class="space-y-4 mt-6">
                <label for="catatanSelect" class="block text-sm font-semibold text-accent-700">Pilih Perjawatan</label>
                <div class="relative">
                    <select id="catatanSelect" onchange="loadChartWithCatatan()" disabled class="w-full p-4 pr-12 border-2 border-accent-200 rounded-xl text-base bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300 appearance-none cursor-pointer hover:border-accent-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Pilih perjawatan...</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <svg class="w-5 h-5 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div id="message" class="mb-6"></div>
        
        <!-- Chart Container -->
        <div class="hidden animate-slide-up" id="chartContainer">
            <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-accent-200/50 p-6 sm:p-8">
                <!-- Data Category Section -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 mb-6 border-l-4 border-blue-500">
                    <div class="flex items-center mb-2">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Data Category</h3>
                    </div>
                    <p class="text-sm text-gray-600 ml-11" id="chartTitle">Interactive chart showing data distribution</p>
                </div>
                
                <!-- Chart Area with Data Display -->
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Chart Section -->
                    <div class="flex-1 h-96 sm:h-[28rem] relative">
                        <canvas id="dataChart" class="w-full h-full"></canvas>
                    </div>
                    
                    <!-- Data Values Display Box -->
                    <div class="lg:w-80 w-full">
                        <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6 h-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-bold text-red-800">Maklumat Perjawatan</h3>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 border border-red-100">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-700">Jawatan:</span>
                                        <span id="jawatanValue" class="text-xl font-bold text-blue-600">-</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-red-100">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-700">Isi:</span>
                                        <span id="isiValue" class="text-xl font-bold text-purple-600">-</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-red-100">
                                    <div class="flex justify-between items-center">
                                        <span class="font-semibold text-gray-700">Kekosongan:</span>
                                        <span id="kekosonganValue" class="text-xl font-bold text-green-600">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-4 mb-4">
                                <button id="exportButton" onclick="downloadChart()" class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 group text-sm" title="Export Chart">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="font-medium">Export</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Perjawatan Section -->
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-accent-200/50 p-6 sm:p-8 mt-8 animate-fade-in">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 mb-6 border-l-4 border-green-500">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Statistics Perjawatan</h3>
                </div>
                <p class="text-sm text-gray-600 ml-11">Jumlah perjawatan bagi semua jawatan mengikut format (JIK)</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-1">Jumlah Perjawatan</p>
                            <p class="text-2xl font-bold text-blue-800" id="totalJawatan">0</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-600 mb-1">Jumlah Isi</p>
                            <p class="text-2xl font-bold text-purple-800" id="totalIsi">0</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-green-600 mb-1">Jumlah Kekosongan</p>
                            <p class="text-2xl font-bold text-green-800" id="totalKekosongan">0</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <hr class="mb-4">
                    <div id="catatanSection" class="mb-4" style="display: none;">
                        <p class="text-sm font-medium text-green-600 mb-2">Perjawatan yang mempunyai kekosongan</p>
                        <div id="catatanList" class="text-xs text-gray-700 space-y-1">
                            <!-- Catatan bullet points will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let chart = null;

        // Function to calculate totals across all records for a category
        function calculateCategoryTotals(data, category) {
            const categoryFieldMap = {
                'PEJ TPKN (KA) JKN': 'ptpknka',
                'PKD KOTA STAR': 'pkdks',
                'PKD KUALA MUDA': 'pkdkm',
                'PKD KULIM': 'pkdk',
                'PKD KUBANG PASU': 'pkdkp',
                'PKD BALING': 'pkdb',
                'PKD LANGKAWI': 'pkdl',
                'PKD YAN': 'pkdy',
                'PKD PENDANG': 'pkdp',
                'PKD SIK': 'pkds',
                'PKD BANDAR BAHARU': 'pkdbb',
                'VECTOR NEGERI': 'vn',
                'PKPMA BUKIT KAYU HITAM': 'pkpmabkh'
            };

            const fieldPrefix = categoryFieldMap[category];
            
            if (!fieldPrefix || !data || data.length === 0) {
                return { jawatan: 0, isi: 0, kekosongan: 0, catatanWithKekosongan: [] };
            }

            let totalJawatan = 0;
            let totalIsi = 0;
            let totalKekosongan = 0;
            let catatanWithKekosongan = [];

            // Sum up values from all records
            data.forEach((record, index) => {
                const jawatanField = `${fieldPrefix}_j`;
                const isiField = `${fieldPrefix}_i`;
                const kekosonganField = `${fieldPrefix}_k`;

                if (record.hasOwnProperty(jawatanField)) {
                    const value = record[jawatanField];
                    const numValue = (value === '' || value === null || value === undefined) ? 0 : (parseInt(value) || 0);
                    totalJawatan += numValue;
                }

                if (record.hasOwnProperty(isiField)) {
                    const value = record[isiField];
                    const numValue = (value === '' || value === null || value === undefined) ? 0 : (parseInt(value) || 0);
                    totalIsi += numValue;
                }

                if (record.hasOwnProperty(kekosonganField)) {
                    const value = record[kekosonganField];
                    const numValue = (value === '' || value === null || value === undefined) ? 0 : (parseInt(value) || 0);
                    totalKekosongan += numValue;
                    
                    // If kekosongan > 0, collect the Catatan
                    if (numValue > 0 && record.hasOwnProperty('Catatan') && record.Catatan && record.Catatan.trim() !== '') {
                        catatanWithKekosongan.push(record.Catatan.trim());
                    }
                }
            });

            const result = {
                jawatan: totalJawatan,
                isi: totalIsi,
                kekosongan: totalKekosongan,
                catatanWithKekosongan: catatanWithKekosongan
            };
            
            return result;
        }

        // Function to display Catatan as bullet points with "See More" functionality
        function displayCatatanList(catatanArray) {
            const catatanListElement = document.getElementById('catatanList');
            const catatanSectionElement = document.getElementById('catatanSection');
            
            if (!catatanListElement || !catatanSectionElement) {
                console.error('catatanList or catatanSection element not found!');
                return;
            }
            
            // Clear existing content
            catatanListElement.innerHTML = '';
            
            if (!catatanArray || catatanArray.length === 0) {
                // Hide the entire Catatan section when there are no vacancies
                catatanSectionElement.style.display = 'none';
                return;
            }
            
            // Show the Catatan section when there are vacancies
            catatanSectionElement.style.display = 'block';
            
            // Remove duplicates and create bullet points
            const uniqueCatatan = [...new Set(catatanArray)];
            const maxDisplayItems = 1; // Show only 1 item initially
            
            // Create bullet points for initial display
            const displayItems = uniqueCatatan.slice(0, maxDisplayItems);
            const bulletPoints = displayItems.map(catatan => 
                `<div class="flex items-start space-x-2">
                    <span class="text-green-500 font-bold mt-0.5">•</span>
                    <span class="text-xs text-gray-700 leading-relaxed">${catatan}</span>
                </div>`
            ).join('');
            
            // Add "See More" button if there are more items
            let seeMoreButton = '';
            if (uniqueCatatan.length > maxDisplayItems) {
                const remainingCount = uniqueCatatan.length - maxDisplayItems;
                seeMoreButton = `
                    <div class="mt-2 text-end">
                        <button onclick="openCatatanPopup()" class="text-xs text-blue-600 hover:text-blue-800 underline font-medium transition-colors duration-200">
                            See More (+${remainingCount} more)
                        </button>
                    </div>
                `;
            }
            
            catatanListElement.innerHTML = bulletPoints + seeMoreButton;
            
            // Store all catatan data globally for popup use
            window.allCatatanData = uniqueCatatan;
        }

        // Function to open Catatan popup
        function openCatatanPopup() {
            const modal = document.getElementById('catatanModal');
            const modalContent = document.getElementById('catatanModalContent');
            
            if (!window.allCatatanData || window.allCatatanData.length === 0) {
                return;
            }
            
            // Create bullet points for all items
            const allBulletPoints = window.allCatatanData.map(catatan => 
                `<div class="flex items-start space-x-3 py-2">
                    <span class="text-green-500 font-bold mt-1 text-sm">•</span>
                    <span class="text-sm text-gray-700 leading-relaxed">${catatan}</span>
                </div>`
            ).join('');
            
            modalContent.innerHTML = allBulletPoints;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        // Function to close Catatan popup
        function closeCatatanPopup() {
            const modal = document.getElementById('catatanModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore background scrolling
        }

        // Function to update the Statistics Perjawatan section
        function updateStatisticsPerjawatan(totals) {
            const jawatanElement = document.getElementById('totalJawatan');
            const isiElement = document.getElementById('totalIsi');
            const kekosonganElement = document.getElementById('totalKekosongan');
            
            if (jawatanElement) {
                jawatanElement.textContent = totals.jawatan;
            } else {
                console.error('totalJawatan element not found!');
            }
            
            if (isiElement) {
                isiElement.textContent = totals.isi;
            } else {
                console.error('totalIsi element not found!');
            }
            
            if (kekosonganElement) {
                kekosonganElement.textContent = totals.kekosongan;
            } else {
                console.error('totalKekosongan element not found!');
            }
            
            // Display Catatan list for positions with kekosongan > 0
            if (totals.catatanWithKekosongan) {
                displayCatatanList(totals.catatanWithKekosongan);
            }
        }

        async function loadChart() {
            const select = document.getElementById('dataSelect');
            const catatanSelect = document.getElementById('catatanSelect');
            const messageDiv = document.getElementById('message');
            const chartContainer = document.getElementById('chartContainer');
            const chartTitle = document.getElementById('chartTitle');
            
            if (select.value === '') {
                chartContainer.classList.add('hidden');
                messageDiv.innerHTML = '';
                catatanSelect.disabled = true;
                catatanSelect.innerHTML = '<option value="">Pilih perjawatan...</option>';
                // Reset totals when no category is selected
                updateStatisticsPerjawatan({ jawatan: 0, isi: 0, kekosongan: 0 });
                return;
            }
            
            // Enable catatan select when category is chosen
            catatanSelect.disabled = false;
            
            // Don't show chart yet - wait for catatan selection
            chartContainer.classList.add('hidden');
            messageDiv.innerHTML = `
                <div class="bg-blue-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-blue-200/50 p-6 animate-fade-in">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-800">Category Selected</h3>
                            <p class="text-sm text-blue-600 mt-1">Loading data and calculating totals...</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Calculate and display totals for the selected category
            try {
                const response = await fetch('b.php');
                const data = await response.json();

                if (data.status === 'success' && data.data && data.data.perjawatan) {
                    const totals = calculateCategoryTotals(data.data.perjawatan, select.value);
                    updateStatisticsPerjawatan(totals);
                } else {
                    console.error('Error in data response:', data);
                    updateStatisticsPerjawatan({ jawatan: 0, isi: 0, kekosongan: 0 });
                }
            } catch (error) {
                console.error('Error calculating totals:', error);
                updateStatisticsPerjawatan({ jawatan: 0, isi: 0, kekosongan: 0 });
            }

            return;

            // Show loading state with modern design
            messageDiv.innerHTML = `
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-accent-200/50 p-6 animate-fade-in">
                    <div class="flex items-center justify-center space-x-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary-500 border-t-transparent"></div>
                        <span class="text-accent-700 font-medium">Loading data analytics...</span>
                    </div>
                </div>
            `;
            chartContainer.classList.add('hidden');

            try {
                const response = await fetch('b.php');
                const data = await response.json();

                if (data.status === 'error') {
                    throw new Error(data.message);
                }

                // Filter data for the specific category
                const categoryData = filterDataByCategory(data.data.perjawatan, select.value);
                
                if (categoryData.length === 0) {
                    messageDiv.innerHTML = `
                        <div class="bg-red-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-red-200/50 p-6 animate-fade-in">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-red-800">No Data Available</h3>
                                    <p class="text-sm text-red-600 mt-1">No data found for the selected category. Please try another selection.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    return;
                }

                // Update chart title
                chartTitle.textContent = `${select.value} Analytics`;

                // Create chart with the filtered data
                createChart(categoryData, select.value);
                chartContainer.classList.remove('hidden');
                
                // Show success message
                messageDiv.innerHTML = `
                    <div class="bg-green-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-green-200/50 p-6 animate-fade-in">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-green-800">Data Loaded Successfully</h3>
                                <p class="text-sm text-green-600 mt-1">Chart visualization is now ready for analysis.</p>
                            </div>
                        </div>
                    </div>
                `;

                // Auto-hide success message after 3 seconds
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 3000);

            } catch (error) {
                messageDiv.innerHTML = `
                    <div class="bg-red-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-red-200/50 p-6 animate-fade-in">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-red-800">Error Loading Data</h3>
                                <p class="text-sm text-red-600 mt-1">${error.message}</p>
                            </div>
                        </div>
                    </div>
                `;
                chartContainer.classList.add('hidden');
            }
        }

        function filterDataByCategory(data, category) {
            // Category to field prefix mapping
            const categoryFieldMap = {
                'PEJ TPKN (KA) JKN': 'ptpknka',
                'PKD KOTA STAR': 'pkdks',
                'PKD KUALA MUDA': 'pkdkm',
                'PKD KULIM': 'pkdk',
                'PKD KUBANG PASU': 'pkdkp',
                'PKD BALING': 'pkdb',
                'PKD LANGKAWI': 'pkdl',
                'PKD YAN': 'pkdy',
                'PKD PENDANG': 'pkdp',
                'PKD SIK': 'pkds',
                'PKD BANDAR BAHARU': 'pkdbb',
                'VECTOR NEGERI': 'vn',
                'PKPMA BUKIT KAYU HITAM': 'pkpmabkh',
                'Catatan': 'catatan'
            };

            const fieldPrefix = categoryFieldMap[category];
            if (!fieldPrefix || !data || data.length === 0) {
                return [];
            }

            // Get the first record (assuming single record structure)
            const record = data[0];
            
            // Extract j, i, k values for the category
            const categoryData = [];
            const suffixes = ['j', 'i', 'k'];
            
            suffixes.forEach(suffix => {
                const fieldName = `${fieldPrefix}_${suffix}`;
                if (record.hasOwnProperty(fieldName)) {
                    const value = record[fieldName];
                    // Convert to number, default to 0 if empty or invalid
                    const numValue = value === '' || value === null || value === undefined ? 0 : parseInt(value) || 0;
                    categoryData.push({
                        name: fieldName,
                        value: numValue
                    });
                }
            });

            return categoryData;
        }

        function createChart(data, categoryName) {
            const ctx = document.getElementById('dataChart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (chart) {
                chart.destroy();
            }
            // Untuk label chart 
            const labels = data.map(item => {
                const fieldName = item.name.toLowerCase();
                if (fieldName.includes('_j')) {
                    return 'Jawatan';
                } else if (fieldName.includes('_i')) {
                    return 'Isi';
                } else if (fieldName.includes('_k')) {
                    return 'Kekosongan';
                } else {
                    return item.name.toUpperCase();
                }
            });
            const values = data.map(item => item.value);
            
            // Modern gradient colors
            const gradientColors = [
                {
                    bg: 'rgba(14, 165, 233, 0.8)',
                    border: 'rgba(14, 165, 233, 1)',
                    gradient: ['rgba(14, 165, 233, 0.8)', 'rgba(14, 165, 233, 0.4)']
                },
                {
                    bg: 'rgba(168, 85, 247, 0.8)',
                    border: 'rgba(168, 85, 247, 1)',
                    gradient: ['rgba(168, 85, 247, 0.8)', 'rgba(168, 85, 247, 0.4)']
                },
                {
                    bg: 'rgba(34, 197, 94, 0.8)',
                    border: 'rgba(34, 197, 94, 1)',
                    gradient: ['rgba(34, 197, 94, 0.8)', 'rgba(34, 197, 94, 0.4)']
                }
            ];

            // Create gradients for bars
            const backgroundColors = values.map((_, index) => {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                const colorSet = gradientColors[index % gradientColors.length];
                gradient.addColorStop(0, colorSet.gradient[0]);
                gradient.addColorStop(1, colorSet.gradient[1]);
                return gradient;
            });

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Count',
                        data: values,
                        backgroundColor: backgroundColors,
                        borderColor: gradientColors.map(c => c.border),
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleColor: '#f8fafc',
                            bodyColor: '#f8fafc',
                            borderColor: 'rgba(100, 116, 139, 0.2)',
                            borderWidth: 1,
                            cornerRadius: 12,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return `${categoryName}`;
                                },
                                label: function(context) {
                                    return `${context.label}: ${context.parsed.y} records`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: {
                                    family: 'Inter',
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.5)',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                color: '#64748b',
                                font: {
                                    family: 'Inter',
                                    size: 12
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            
            // Update data values in the red box
            updateDataValues(data);
        }
        
        function updateDataValues(data) {
            // Initialize values
            let jawatanValue = 0;
            let isiValue = 0;
            let kekosonganValue = 0;
            
            // Extract values from data
            data.forEach(item => {
                const fieldName = item.name.toLowerCase();
                if (fieldName.includes('_j')) {
                    jawatanValue = item.value;
                } else if (fieldName.includes('_i')) {
                    isiValue = item.value;
                } else if (fieldName.includes('_k')) {
                    kekosonganValue = item.value;
                }
            });
            
            // Update the display elements
            document.getElementById('jawatanValue').textContent = jawatanValue;
            document.getElementById('isiValue').textContent = isiValue;
            document.getElementById('kekosonganValue').textContent = kekosonganValue;
        }

        // Function to populate Catatan options based on selected category
        async function populateCatatanOptions(selectedCategory) {
            const catatanSelect = document.getElementById('catatanSelect');
            
            // Clear existing options except the first placeholder
            catatanSelect.innerHTML = '<option value="">Pilih perjawatan...</option>';
            
            if (!selectedCategory) {
                catatanSelect.disabled = true;
                return;
            }

            try {
                const response = await fetch('b.php');
                const data = await response.json();

                if (data.status === 'error') {
                    throw new Error(data.message);
                }

                // Get unique Catatan values from the data
                const catatanValues = new Set();
                
                if (data.data && data.data.perjawatan && data.data.perjawatan.length > 0) {
                    data.data.perjawatan.forEach(record => {
                        // Look for catatan fields in the record
                        Object.keys(record).forEach(key => {
                            if (key.toLowerCase().includes('catatan') && record[key] && record[key].trim() !== '') {
                                catatanValues.add(record[key].trim());
                            }
                        });
                    });
                }

                // Populate the select with unique catatan values
                if (catatanValues.size > 0) {
                    catatanValues.forEach(value => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        catatanSelect.appendChild(option);
                    });
                    catatanSelect.disabled = false;
                } else {
                    // Add a "No Catatan Available" option
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No Catatan available for this category';
                    option.disabled = true;
                    catatanSelect.appendChild(option);
                    catatanSelect.disabled = true;
                }

            } catch (error) {
                console.error('Error populating Catatan options:', error);
                catatanSelect.disabled = true;
            }
        }

        // Function to load chart with both category and catatan filtering
        async function loadChartWithCatatan() {
            const categorySelect = document.getElementById('dataSelect');
            const catatanSelect = document.getElementById('catatanSelect');
            const messageDiv = document.getElementById('message');
            const chartContainer = document.getElementById('chartContainer');
            const chartTitle = document.getElementById('chartTitle');
            
            const selectedCategory = categorySelect.value;
            const selectedCatatan = catatanSelect.value;
            
            // Hide chart if either category or catatan is not selected
            if (!selectedCategory || !selectedCatatan) {
                chartContainer.classList.add('hidden');
                
                if (!selectedCategory) {
                    messageDiv.innerHTML = `
                        <div class="bg-yellow-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-yellow-200/50 p-6 animate-fade-in">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-yellow-800">Category Required</h3>
                                    <p class="text-sm text-yellow-600 mt-1">Please select a category first.</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (!selectedCatatan) {
                    messageDiv.innerHTML = `
                        <div class="bg-blue-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-blue-200/50 p-6 animate-fade-in">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-blue-800">Catatan Required</h3>
                                    <p class="text-sm text-blue-600 mt-1">Please select a Catatan to view the chart data.</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
                return;
            }

            // Show loading state
            messageDiv.innerHTML = `
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-accent-200/50 p-6 animate-fade-in">
                    <div class="flex items-center justify-center space-x-3">
                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-primary-500 border-t-transparent"></div>
                        <span class="text-accent-700 font-medium">Loading filtered data analytics...</span>
                    </div>
                </div>
            `;
            chartContainer.classList.add('hidden');

            try {
                const response = await fetch('b.php');
                const data = await response.json();

                if (data.status === 'error') {
                    throw new Error(data.message);
                }

                // Filter data by both category and catatan
                const filteredData = filterDataByCategoryAndCatatan(data.data.perjawatan, selectedCategory, selectedCatatan);
                
                if (filteredData.length === 0) {
                    messageDiv.innerHTML = `
                        <div class="bg-yellow-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-yellow-200/50 p-6 animate-fade-in">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-yellow-800">No Matching Data</h3>
                                    <p class="text-sm text-yellow-600 mt-1">No data found for the selected category and catatan combination.</p>
                                </div>
                            </div>
                        </div>
                    `;
                    return;
                }

                // Update chart title
                chartTitle.textContent = `${selectedCategory} - ${selectedCatatan} Analytics`;

                // Create chart with the filtered data
                createChart(filteredData, `${selectedCategory} - ${selectedCatatan}`);
                chartContainer.classList.remove('hidden');
                
                // Show success message
                messageDiv.innerHTML = `
                    <div class="bg-green-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-green-200/50 p-6 animate-fade-in">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-green-800">Filtered Data Loaded</h3>
                                <p class="text-sm text-green-600 mt-1">Chart shows data for selected category and catatan.</p>
                            </div>
                        </div>
                    </div>
                `;

                // Auto-hide success message after 3 seconds
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 3000);

            } catch (error) {
                messageDiv.innerHTML = `
                    <div class="bg-red-50/90 backdrop-blur-sm rounded-2xl shadow-lg border border-red-200/50 p-6 animate-fade-in">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-red-800">Error Loading Data</h3>
                                <p class="text-sm text-red-600 mt-1">${error.message}</p>
                            </div>
                        </div>
                    </div>
                `;
                chartContainer.classList.add('hidden');
                // Reset totals on error
                updateStatisticsPerjawatan({ jawatan: 0, isi: 0, kekosongan: 0 });
            }
        }

        // Function to filter data by both category and catatan
        function filterDataByCategoryAndCatatan(data, category, catatan) {
            if (!data || data.length === 0) {
                return [];
            }

            // Find records that match the catatan value
            const matchingRecords = data.filter(record => {
                // Check if any catatan field matches the selected catatan
                return Object.keys(record).some(key => 
                    key.toLowerCase().includes('catatan') && 
                    record[key] && 
                    record[key].trim() === catatan
                );
            });

            if (matchingRecords.length === 0) {
                return [];
            }

            // Now filter by category for the matching records
            const categoryFieldMap = {
                'PEJ TPKN (KA) JKN': 'ptpknka',
                'PKD KOTA STAR': 'pkdks',
                'PKD KUALA MUDA': 'pkdkm',
                'PKD KULIM': 'pkdk',
                'PKD KUBANG PASU': 'pkdkp',
                'PKD BALING': 'pkdb',
                'PKD LANGKAWI': 'pkdl',
                'PKD YAN': 'pkdy',
                'PKD PENDANG': 'pkdp',
                'PKD SIK': 'pkds',
                'PKD BANDAR BAHARU': 'pkdbb',
                'VECTOR NEGERI': 'vn',
                'PKPMA BUKIT KAYU HITAM': 'pkpmabkh',
                'Catatan': 'catatan'
            };

            const fieldPrefix = categoryFieldMap[category];
            if (!fieldPrefix) {
                return [];
            }

            // Extract data from matching records
            const categoryData = [];
            const suffixes = ['j', 'i', 'k'];
            
            matchingRecords.forEach((record, index) => {
                suffixes.forEach(suffix => {
                    const fieldName = `${fieldPrefix}_${suffix}`;
                    if (record.hasOwnProperty(fieldName)) {
                        const value = record[fieldName];
                        const numValue = (value === '' || value === null || value === undefined) ? 0 : parseInt(value) || 0;
                        categoryData.push({
                            name: `${fieldName}_record${index + 1}`,
                            value: numValue
                        });
                    }
                });
            });

            return categoryData;
        }

        // Export chart functionality
        function downloadChart() {
            const chartContainer = document.getElementById('chartContainer');
            const exportButton = document.getElementById('exportButton');
            
            if (!chartContainer || chartContainer.classList.contains('hidden')) {
                alert('Please select a category and catatan to generate a chart first.');
                return;
            }
            
            // Hide the export button before taking screenshot
            if (exportButton) {
                exportButton.style.display = 'none';
            }
            
            // Temporarily disable backdrop filters to prevent dark tint
            const elementsWithBackdrop = chartContainer.querySelectorAll('[class*="backdrop-blur"]');
            const originalBackdropFilters = [];
            
            elementsWithBackdrop.forEach((element, index) => {
                originalBackdropFilters[index] = element.style.backdropFilter;
                element.style.backdropFilter = 'none';
            });
            
            // Also handle elements with inline backdrop-filter styles
            const allElements = chartContainer.querySelectorAll('*');
            const originalInlineBackdrops = [];
            
            allElements.forEach((element, index) => {
                const computedStyle = window.getComputedStyle(element);
                if (computedStyle.backdropFilter && computedStyle.backdropFilter !== 'none') {
                    originalInlineBackdrops[index] = element.style.backdropFilter;
                    element.style.backdropFilter = 'none';
                }
            });
            
            // Use html2canvas to capture the entire container
            html2canvas(chartContainer, {
                backgroundColor: '#ffffff',
                scale: 2, // Higher quality
                useCORS: true,
                allowTaint: true,
                scrollX: 0,
                scrollY: 0,
                width: chartContainer.scrollWidth,
                height: chartContainer.scrollHeight
            }).then(function(canvas) {
                // Restore backdrop filters
                elementsWithBackdrop.forEach((element, index) => {
                    element.style.backdropFilter = originalBackdropFilters[index] || '';
                });
                
                allElements.forEach((element, index) => {
                    if (originalInlineBackdrops[index] !== undefined) {
                        element.style.backdropFilter = originalInlineBackdrops[index] || '';
                    }
                });
                
                // Show the export button again after screenshot
                if (exportButton) {
                    exportButton.style.display = 'flex';
                }
                
                // Create download link
                const link = document.createElement('a');
                link.download = 'hospital-data-dashboard.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(function(error) {
                // Restore backdrop filters even on error
                elementsWithBackdrop.forEach((element, index) => {
                    element.style.backdropFilter = originalBackdropFilters[index] || '';
                });
                
                allElements.forEach((element, index) => {
                    if (originalInlineBackdrops[index] !== undefined) {
                        element.style.backdropFilter = originalInlineBackdrops[index] || '';
                    }
                });
                
                // Show the export button again even if there's an error
                if (exportButton) {
                    exportButton.style.display = 'flex';
                }
                console.error('Error capturing screenshot:', error);
                alert('Failed to capture screenshot. Please try again.');
            });
        }

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && document.activeElement.id === 'dataSelect') {
                loadChart();
            }
        });

        // Initialize Select2 with search functionality
        $(document).ready(function() {
            $('#dataSelect').select2({
                placeholder: "Pilih PTJ...",
                allowClear: true,
                width: '100%',
                theme: 'default',
                dropdownCssClass: 'select2-modern-dropdown',
                selectionCssClass: 'select2-modern-selection'
            });

            // Handle Select2 change event
            $('#dataSelect').on('select2:select', function (e) {
                const selectedCategory = e.params.data.text;
                populateCatatanOptions(selectedCategory);
                loadChart();
            });

            // Handle Select2 clear event
            $('#dataSelect').on('select2:clear', function (e) {
                // Clear catatan options and disable it
                const catatanSelect = document.getElementById('catatanSelect');
                catatanSelect.innerHTML = '<option value="">Pilih perjawatan...</option>';
                catatanSelect.disabled = true;
                
                if (chart) {
                    chart.destroy();
                    chart = null;
                }
                document.getElementById('chartContainer').classList.add('hidden');
                document.getElementById('message').innerHTML = '';
            });

            // Initialize Catatan Select2
            $('#catatanSelect').select2({
                placeholder: "Pilih perjawatan...",
                allowClear: true,
                width: '100%',
                theme: 'default',
                dropdownCssClass: 'select2-modern-dropdown',
                selectionCssClass: 'select2-modern-selection'
            });

            // Handle Catatan Select2 change event
            $('#catatanSelect').on('select2:select', function (e) {
                loadChartWithCatatan();
            });

            // Handle Catatan Select2 clear event
            $('#catatanSelect').on('select2:clear', function (e) {
                if (chart) {
                    chart.destroy();
                    chart = null;
                }
                document.getElementById('chartContainer').classList.add('hidden');
                document.getElementById('message').innerHTML = '';
            });
        });
    </script>

    <style>
        /* Custom Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: 60px !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(8px) !important;
            transition: all 0.3s ease !important;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #cbd5e1 !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.2) !important;
            outline: none !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important;
            line-height: 56px !important;
            padding-left: 16px !important;
            font-size: 16px !important;
            font-family: 'Inter', sans-serif !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 56px !important;
            right: 12px !important;
        }

        .select2-dropdown {
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 16px !important;
            transition: all 0.2s ease !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0ea5e9 !important;
            color: white !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-family: 'Inter', sans-serif !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #0ea5e9 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1) !important;
        }
    </style>

    <!-- Catatan Modal -->
    <div id="catatanModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Catatan Perjawatan dengan Kekosongan</h3>
                        <p class="text-sm text-gray-500">Senarai lengkap catatan untuk jawatan yang mempunyai kekosongan</p>
                    </div>
                </div>
                <button onclick="closeCatatanPopup()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="catatanModalContent" class="space-y-2">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end p-6 border-t border-gray-200">
                <button onclick="closeCatatanPopup()" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200 font-medium">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</body>
</html>