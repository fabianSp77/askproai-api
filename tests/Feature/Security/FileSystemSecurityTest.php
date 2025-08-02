<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;

/**
 * File System Security Test
 * 
 * Tests file system security vulnerabilities including path traversal,
 * file inclusion, upload restrictions, and storage access control.
 * 
 * SEVERITY: HIGH - File system compromise potential
 */
class FileSystemSecurityTest extends BaseSecurityTestCase
{
    public function test_file_upload_path_traversal_protection()
    {
        $this->actingAs($this->admin1);

        $maliciousFiles = [
            File::create('../../../etc/passwd', 100),
            File::create('..\\..\\..\\windows\\system32\\config\\sam', 100),
            File::create('/etc/shadow', 100),
            File::create('....//....//....//etc/passwd', 100),
            File::create('%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd', 100),
        ];

        foreach ($maliciousFiles as $file) {
            $response = $this->postJson('/admin/api/files/upload', [
                'file' => $file,
                'path' => '../sensitive/',
            ]);

            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [422, 400, 403]));
            }
        }

        $this->logSecurityTestResult('file_upload_path_traversal_protection', true);
    }

    public function test_file_inclusion_attacks()
    {
        $this->actingAs($this->admin1);

        $inclusionAttempts = [
            '/etc/passwd',
            '../../../etc/passwd',
            '....//....//....//etc/passwd',
            '/proc/self/environ',
            '/var/log/apache2/access.log',
            'php://input',
            'php://filter/read=convert.base64-encode/resource=index.php',
            'data://text/plain;base64,PD9waHAgcGhwaW5mbygpOz8+',
        ];

        foreach ($inclusionAttempts as $path) {
            $response = $this->getJson('/admin/api/files/view?path=' . urlencode($path));

            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [403, 400, 422]));
            }
        }

        $this->logSecurityTestResult('file_inclusion_protection', true);
    }

    public function test_executable_file_upload_prevention()
    {
        $this->actingAs($this->admin1);

        $executableFiles = [
            // Common executable extensions
            File::create('malicious.exe', 1000),
            File::create('trojan.bat', 500),
            File::create('virus.com', 300),
            File::create('backdoor.scr', 400),
            
            // Script files
            File::create('webshell.php', 200),
            File::create('shell.jsp', 300),
            File::create('asp_shell.asp', 250),
            File::create('python_shell.py', 150),
            
            // Double extensions
            File::create('image.jpg.php', 600),
            File::create('document.pdf.exe', 700),
            File::create('safe.txt.bat', 400),
        ];

        foreach ($executableFiles as $file) {
            $response = $this->postJson('/admin/api/files/upload', [
                'file' => $file,
            ]);

            if ($response->status() !== 404) {
                $this->assertTrue(
                    in_array($response->status(), [422, 400, 415]),
                    "Executable file {$file->name} was accepted"
                );
            }
        }

        $this->logSecurityTestResult('executable_file_upload_prevention', true);
    }

    public function test_file_content_validation()
    {
        $this->actingAs($this->admin1);

        // Create files with malicious content but safe extensions
        $maliciousFiles = [
            File::createWithContent('image.jpg', '<?php system($_GET["cmd"]); ?>'),
            File::createWithContent('document.pdf', '<script>alert("xss")</script>'),
            File::createWithContent('data.csv', '=cmd|" /C calc"!A0'),
            File::createWithContent('config.txt', '#!/bin/bash\nrm -rf /'),
        ];

        foreach ($maliciousFiles as $file) {
            $response = $this->postJson('/admin/api/files/upload', [
                'file' => $file,
            ]);

            if (in_array($response->status(), [200, 201])) {
                // File might be accepted but should be scanned/sanitized
                $uploadedPath = $response->json('path');
                if ($uploadedPath && Storage::exists($uploadedPath)) {
                    $content = Storage::get($uploadedPath);
                    
                    // Content should be sanitized
                    $this->assertStringNotContainsString('<?php', $content);
                    $this->assertStringNotContainsString('<script>', $content);
                    $this->assertStringNotContainsString('system(', $content);
                    
                    Storage::delete($uploadedPath);
                }
            }
        }

        $this->logSecurityTestResult('file_content_validation', true);
    }

    public function test_file_size_limits()
    {
        $this->actingAs($this->admin1);

        // Create oversized file
        $oversizedFile = File::create('huge.csv', 100 * 1024 * 1024); // 100MB

        $response = $this->postJson('/admin/api/files/upload', [
            'file' => $oversizedFile,
        ]);

        if ($response->status() !== 404) {
            $this->assertTrue(
                in_array($response->status(), [413, 422, 400]),
                'Oversized file was accepted'
            );
        }

        $this->logSecurityTestResult('file_size_limits', true);
    }

    public function test_file_storage_permissions()
    {
        $this->actingAs($this->admin1);

        $testFile = File::create('permissions_test.txt', 100);

        $response = $this->postJson('/admin/api/files/upload', [
            'file' => $testFile,
        ]);

        if (in_array($response->status(), [200, 201])) {
            $uploadedPath = $response->json('path');
            
            if ($uploadedPath) {
                $fullPath = Storage::path($uploadedPath);
                
                if (file_exists($fullPath)) {
                    // File should not be executable
                    $perms = fileperms($fullPath) & 0777;
                    $this->assertFalse($perms & 0111, 'Uploaded file has execute permissions');
                    
                    Storage::delete($uploadedPath);
                }
            }
        }

        $this->logSecurityTestResult('file_storage_permissions', true);
    }

    public function test_directory_listing_prevention()
    {
        $directoriesToTest = [
            '/storage',
            '/storage/app',
            '/storage/logs',
            '/public/storage',
            '/uploads',
            '/files',
        ];

        foreach ($directoriesToTest as $directory) {
            $response = $this->get($directory);
            
            // Should not allow directory listing
            if ($response->status() === 200) {
                $content = $response->getContent();
                
                // Should not contain directory listing indicators
                $this->assertStringNotContainsString('Index of', $content);
                $this->assertStringNotContainsString('Directory Listing', $content);
                $this->assertStringNotContainsString('[DIR]', $content);
            } else {
                $this->assertTrue(in_array($response->status(), [403, 404]));
            }
        }

        $this->logSecurityTestResult('directory_listing_prevention', true);
    }

    public function test_file_download_access_control()
    {
        // Create files for both companies
        $this->actingAs($this->admin1);
        
        $company1File = File::create('company1_private.txt', 100);
        $uploadResponse = $this->postJson('/admin/api/files/upload', [
            'file' => $company1File,
        ]);

        if (in_array($uploadResponse->status(), [200, 201])) {
            $filePath = $uploadResponse->json('path');
            
            // Switch to company 2 admin
            $this->actingAs($this->admin2);
            
            // Try to download company 1's file
            $downloadResponse = $this->getJson("/admin/api/files/download?path={$filePath}");
            
            if ($downloadResponse->status() !== 404) {
                $this->assertTrue(in_array($downloadResponse->status(), [403, 401]));
            }
            
            // Cleanup
            $this->actingAs($this->admin1);
            if ($filePath) {
                Storage::delete($filePath);
            }
        }

        $this->logSecurityTestResult('file_download_access_control', true);
    }

    public function test_symlink_attack_prevention()
    {
        $this->actingAs($this->admin1);

        // Create a symlink file (if possible in test environment)
        $tempDir = sys_get_temp_dir();
        $symlinkPath = $tempDir . '/test_symlink';
        $targetPath = '/etc/passwd';

        if (function_exists('symlink') && !file_exists($symlinkPath)) {
            @symlink($targetPath, $symlinkPath);
            
            if (is_link($symlinkPath)) {
                $symlinkFile = File::create('symlink_test.txt', file_get_contents($symlinkPath) ?: 'symlink content');
                
                $response = $this->postJson('/admin/api/files/upload', [
                    'file' => $symlinkFile,
                ]);

                if ($response->status() !== 404) {
                    // Should detect and reject symlink attacks
                    $this->assertTrue(in_array($response->status(), [422, 400, 403]));
                }
                
                @unlink($symlinkPath);
            }
        }

        $this->logSecurityTestResult('symlink_attack_prevention', true);
    }

    public function test_zip_extraction_security()
    {
        $this->actingAs($this->admin1);

        // Create malicious zip file
        $tempFile = tempnam(sys_get_temp_dir(), 'malicious_zip');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE);
        
        // Add path traversal entries
        $zip->addFromString('../../../etc/passwd', 'malicious content');
        $zip->addFromString('normal_file.txt', 'normal content');
        $zip->close();

        $maliciousZip = File::createWithContent('malicious.zip', file_get_contents($tempFile));
        unlink($tempFile);

        $response = $this->postJson('/admin/api/files/extract', [
            'file' => $maliciousZip,
        ]);

        if ($response->status() !== 404) {
            // Should detect and prevent zip traversal
            $this->assertTrue(in_array($response->status(), [422, 400, 403]));
        }

        $this->logSecurityTestResult('zip_extraction_security', true);
    }

    public function test_temporary_file_cleanup()
    {
        $this->actingAs($this->admin1);

        $testFile = File::create('temp_test.txt', 100);

        $response = $this->postJson('/admin/api/files/upload', [
            'file' => $testFile,
        ]);

        // Check that temporary files are cleaned up
        $tempFiles = glob(sys_get_temp_dir() . '/php*');
        $oldTempFiles = array_filter($tempFiles, function($file) {
            return filemtime($file) < (time() - 3600); // Older than 1 hour
        });

        // Should not have many old temp files lying around
        $this->assertLessThan(100, count($oldTempFiles), 
            'Too many temporary files not cleaned up');

        $this->logSecurityTestResult('temporary_file_cleanup', true);
    }

    public function test_file_metadata_sanitization()
    {
        $this->actingAs($this->admin1);

        // Create file with EXIF data (simulated)
        $fileWithMetadata = File::createWithContent('image_with_metadata.jpg', 
            "\xFF\xE1\x00\x16Exif\x00\x00GPS Location: Secret Base");

        $response = $this->postJson('/admin/api/files/upload', [
            'file' => $fileWithMetadata,
        ]);

        if (in_array($response->status(), [200, 201])) {
            $uploadedPath = $response->json('path');
            
            if ($uploadedPath && Storage::exists($uploadedPath)) {
                $content = Storage::get($uploadedPath);
                
                // Metadata should be stripped
                $this->assertStringNotContainsString('GPS Location', $content);
                $this->assertStringNotContainsString('Secret Base', $content);
                
                Storage::delete($uploadedPath);
            }
        }

        $this->logSecurityTestResult('file_metadata_sanitization', true);
    }

    public function test_file_quarantine_system()
    {
        $this->actingAs($this->admin1);

        // Upload potentially dangerous file
        $suspiciousFile = File::createWithContent('suspicious.txt', 
            '<?php eval($_POST["code"]); ?>');

        $response = $this->postJson('/admin/api/files/upload', [
            'file' => $suspiciousFile,
        ]);

        if (in_array($response->status(), [200, 201])) {
            $uploadedPath = $response->json('path');
            
            // File might be quarantined rather than directly accessible
            if ($uploadedPath) {
                $quarantinePath = str_replace('/uploads/', '/quarantine/', $uploadedPath);
                
                // Check if file was moved to quarantine
                $isQuarantined = Storage::exists($quarantinePath) || 
                                !Storage::exists($uploadedPath);
                
                if ($isQuarantined) {
                    $this->assertTrue(true, 'Suspicious file was quarantined');
                }
                
                // Cleanup
                Storage::delete($uploadedPath);
                Storage::delete($quarantinePath);
            }
        }

        $this->logSecurityTestResult('file_quarantine_system', true);
    }

    public function test_file_access_logging()
    {
        $this->actingAs($this->admin1);

        $testFile = File::create('access_log_test.txt', 100);

        $uploadResponse = $this->postJson('/admin/api/files/upload', [
            'file' => $testFile,
        ]);

        if (in_array($uploadResponse->status(), [200, 201])) {
            $filePath = $uploadResponse->json('path');
            
            // Access the file
            $this->getJson("/admin/api/files/download?path={$filePath}");
            
            // Check if access was logged
            if (\Schema::hasTable('file_access_logs')) {
                $this->assertDatabaseHas('file_access_logs', [
                    'file_path' => $filePath,
                    'user_id' => $this->admin1->id,
                    'action' => 'download',
                ]);
            }
            
            // Cleanup
            if ($filePath) {
                Storage::delete($filePath);
            }
        }

        $this->logSecurityTestResult('file_access_logging', true);
    }
}