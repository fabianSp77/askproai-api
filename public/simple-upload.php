<?php
// Simple Flowbite Upload Handler with Better Error Handling
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('max_execution_time', 600); // 10 minutes
ini_set('memory_limit', '1G');

header('Access-Control-Allow-Origin: *');

// Check current limits
echo "📊 Server Configuration:\n";
echo "- Upload Max: " . ini_get('upload_max_filesize') . "\n";
echo "- POST Max: " . ini_get('post_max_size') . "\n";
echo "- Memory Limit: " . ini_get('memory_limit') . "\n";
echo "- Max Execution: " . ini_get('max_execution_time') . "s\n\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "📤 Upload Request Received\n";
    
    // Check for upload errors
    if (!isset($_FILES['file'])) {
        die("❌ No files received. Check that files were selected.\n");
    }
    
    $uploadedFile = $_FILES['file'];
    
    // Check for errors
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        
        $errorMsg = $errorMessages[$uploadedFile['error']] ?? 'Unknown error';
        die("❌ Upload Error: " . $errorMsg . " (Code: " . $uploadedFile['error'] . ")\n");
    }
    
    // Process the file
    $fileName = $uploadedFile['name'];
    $fileSize = $uploadedFile['size'];
    $tmpName = $uploadedFile['tmp_name'];
    
    echo "✅ File received: $fileName\n";
    echo "📦 Size: " . round($fileSize / 1024 / 1024, 2) . " MB\n";
    
    // Save to /tmp
    $targetPath = '/tmp/' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $fileName);
    
    if (move_uploaded_file($tmpName, $targetPath)) {
        echo "✅ Saved to: $targetPath\n\n";
        
        // Extract and process
        echo "📂 Extracting file...\n";
        $extractDir = '/var/www/api-gateway/resources/flowbite-pro/' . basename($fileName, '.zip');
        
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($targetPath) === TRUE) {
            $zip->extractTo($extractDir);
            $zip->close();
            
            echo "✅ Extracted to: $extractDir\n";
            
            // List contents
            echo "\n📋 Package Contents:\n";
            $files = scandir($extractDir);
            foreach (array_slice($files, 2, 10) as $file) {
                echo "  - $file\n";
            }
            
            if (count($files) > 12) {
                echo "  ... and " . (count($files) - 12) . " more files\n";
            }
            
            echo "\n🎉 Upload and extraction successful!\n";
            echo "📍 Location: $extractDir\n";
            
        } else {
            echo "❌ Failed to extract ZIP file\n";
        }
        
    } else {
        echo "❌ Failed to save file to $targetPath\n";
    }
    
} else {
    // Show upload form
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Flowbite Upload</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-8">
        <h1 class="text-2xl font-bold mb-4">Simple Flowbite Upload</h1>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Select ZIP File (Max 700MB):</label>
                <input type="file" name="file" accept=".zip" required 
                       class="w-full p-2 border rounded">
            </div>
            
            <button type="submit" 
                    class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                Upload File
            </button>
        </form>
        
        <div id="status" class="mt-4 p-4 bg-gray-100 rounded hidden">
            <div class="font-bold mb-2">Upload Status:</div>
            <div id="statusText"></div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded">
            <p class="text-sm text-blue-800">
                <strong>Current Server Limits:</strong><br>
                Upload Max: <?php echo ini_get('upload_max_filesize'); ?><br>
                POST Max: <?php echo ini_get('post_max_size'); ?><br>
                Memory: <?php echo ini_get('memory_limit'); ?>
            </p>
        </div>
    </div>
    
    <script>
        document.getElementById('uploadForm').onsubmit = function(e) {
            document.getElementById('status').classList.remove('hidden');
            document.getElementById('statusText').innerHTML = 
                '<div class="text-blue-600">Uploading... This may take a moment for large files.</div>';
        };
    </script>
</body>
</html>
    <?php
}
?>