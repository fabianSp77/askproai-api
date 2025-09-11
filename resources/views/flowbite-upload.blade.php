<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Flowbite Pro Upload - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .upload-area {
            transition: all 0.3s ease;
        }
        .upload-area.dragover {
            background-color: rgba(59, 130, 246, 0.1);
            border-color: rgb(59, 130, 246);
            transform: scale(1.02);
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    🎨 Flowbite Pro Upload
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Laden Sie Ihre Flowbite Pro Dateien hoch für die automatische Integration
                </p>
            </div>

            <!-- Status Card -->
            <div id="statusCard" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📊 System Status
                </h2>
                <div id="statusContent" class="space-y-2">
                    <div class="flex items-center">
                        <span class="animate-pulse-slow text-yellow-500 mr-2">⏳</span>
                        <span class="text-gray-600 dark:text-gray-400">Lade Status...</span>
                    </div>
                </div>
            </div>

            <!-- Upload Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Upload Area -->
                    <div id="uploadArea" class="upload-area border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition-all">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        
                        <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Ziehen Sie die ZIP-Datei hierher
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            oder klicken Sie zum Auswählen
                        </p>
                        
                        <input type="file" id="fileInput" name="flowbite_file" accept=".zip" class="hidden">
                        
                        <button type="button" id="selectButton" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            Datei auswählen
                        </button>
                    </div>

                    <!-- File Info -->
                    <div id="fileInfo" class="hidden mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z"/>
                                    <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white" id="fileName">-</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" id="fileSize">-</p>
                                </div>
                            </div>
                            <button type="button" id="removeFile" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div id="progressContainer" class="hidden mt-6">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span>Hochladen...</span>
                            <span id="progressText">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div id="progressBar" class="progress-bar bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Upload Button -->
                    <button type="submit" id="uploadButton" class="hidden w-full mt-6 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Hochladen und Integrieren
                    </button>
                </form>

                <!-- Result Message -->
                <div id="resultMessage" class="hidden mt-6"></div>
            </div>

            <!-- Instructions -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📝 Anleitung
                </h3>
                <ol class="space-y-3 text-gray-600 dark:text-gray-400">
                    <li class="flex">
                        <span class="font-bold text-blue-600 mr-3">1.</span>
                        <span>Laden Sie Flowbite Pro von Google Drive herunter:
                            <a href="https://drive.google.com/drive/folders/1MEpv9w12cpdC_upis9VRomEXF47T1KEe" target="_blank" class="text-blue-600 hover:underline ml-1">
                                Zum Google Drive →
                            </a>
                        </span>
                    </li>
                    <li class="flex">
                        <span class="font-bold text-blue-600 mr-3">2.</span>
                        <span>Wählen Sie alle Dateien aus (Strg+A / Cmd+A)</span>
                    </li>
                    <li class="flex">
                        <span class="font-bold text-blue-600 mr-3">3.</span>
                        <span>Rechtsklick → Download (Google erstellt automatisch eine ZIP)</span>
                    </li>
                    <li class="flex">
                        <span class="font-bold text-blue-600 mr-3">4.</span>
                        <span>Laden Sie die ZIP-Datei hier hoch</span>
                    </li>
                </ol>
                
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>Hinweis:</strong> Die Datei sollte nicht größer als 50MB sein. 
                        Nach dem Upload werden die Komponenten automatisch integriert.
                    </p>
                </div>
            </div>

            <!-- Links -->
            <div class="mt-6 text-center space-x-4">
                <a href="/admin" class="text-blue-600 hover:underline">← Zurück zum Admin</a>
                <span class="text-gray-400">•</span>
                <a href="/flowbite-test" class="text-blue-600 hover:underline">Test-Seite →</a>
            </div>
        </div>
    </div>

    <script>
        // Get elements
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const selectButton = document.getElementById('selectButton');
        const uploadForm = document.getElementById('uploadForm');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFile = document.getElementById('removeFile');
        const uploadButton = document.getElementById('uploadButton');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const resultMessage = document.getElementById('resultMessage');

        // Load status on page load
        loadStatus();

        // File selection
        selectButton.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/zip') {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        // File input change
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.remove('hidden');
                uploadButton.classList.remove('hidden');
                uploadArea.classList.add('hidden');
            }
        }

        // Remove file
        removeFile.addEventListener('click', () => {
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            uploadButton.classList.add('hidden');
            uploadArea.classList.remove('hidden');
        });

        // Form submission
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('flowbite_file', fileInput.files[0]);
            formData.append('_token', document.querySelector('[name="csrf-token"]').content);

            // Show progress
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Wird hochgeladen...';
            progressContainer.classList.remove('hidden');

            try {
                const xhr = new XMLHttpRequest();

                // Progress event
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                    }
                });

                // Load event
                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showResult('success', response.message);
                            setTimeout(() => {
                                window.location.href = '/flowbite-test';
                            }, 3000);
                        } else {
                            showResult('error', response.message);
                        }
                    } else {
                        showResult('error', 'Upload fehlgeschlagen');
                    }
                    resetForm();
                });

                // Error event
                xhr.addEventListener('error', () => {
                    showResult('error', 'Netzwerkfehler beim Upload');
                    resetForm();
                });

                // Send request
                xhr.open('POST', '/flowbite-upload');
                xhr.send(formData);

            } catch (error) {
                showResult('error', 'Fehler: ' + error.message);
                resetForm();
            }
        });

        function showResult(type, message) {
            const bgColor = type === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20';
            const textColor = type === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200';
            const icon = type === 'success' ? '✅' : '❌';
            
            resultMessage.className = `p-4 rounded-lg ${bgColor}`;
            resultMessage.innerHTML = `
                <div class="flex items-center">
                    <span class="text-2xl mr-3">${icon}</span>
                    <p class="${textColor}">${message}</p>
                </div>
            `;
            resultMessage.classList.remove('hidden');
        }

        function resetForm() {
            uploadButton.disabled = false;
            uploadButton.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg> Hochladen und Integrieren';
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
            loadStatus();
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        async function loadStatus() {
            try {
                const response = await fetch('/flowbite-status');
                const status = await response.json();
                
                let statusHTML = '';
                
                if (status.files_exist) {
                    statusHTML = `
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="text-green-500 mr-2">✅</span>
                                <span class="text-gray-900 dark:text-white">Flowbite Pro installiert</span>
                            </div>
                            <div class="flex items-center">
                                <span class="text-blue-500 mr-2">📁</span>
                                <span class="text-gray-600 dark:text-gray-400">${status.file_count} Dateien gefunden</span>
                            </div>
                            ${status.components.length > 0 ? `
                            <div class="flex items-center">
                                <span class="text-purple-500 mr-2">🧩</span>
                                <span class="text-gray-600 dark:text-gray-400">Komponenten: ${status.components.join(', ')}</span>
                            </div>
                            ` : ''}
                        </div>
                    `;
                } else {
                    statusHTML = `
                        <div class="flex items-center">
                            <span class="text-yellow-500 mr-2">⚠️</span>
                            <span class="text-gray-600 dark:text-gray-400">Flowbite Pro noch nicht installiert</span>
                        </div>
                    `;
                }
                
                document.getElementById('statusContent').innerHTML = statusHTML;
                
            } catch (error) {
                console.error('Status load error:', error);
            }
        }
    </script>
</body>
</html>