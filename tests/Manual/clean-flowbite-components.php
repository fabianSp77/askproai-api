#!/usr/bin/env php
<?php

/**
 * Clean Flowbite Components - Remove Hugo Frontmatter
 */

$basePath = '/var/www/api-gateway/resources/views/components/flowbite-pro';
$processedCount = 0;
$errorCount = 0;

function cleanComponent($filePath) {
    $content = file_get_contents($filePath);
    
    // Remove Hugo frontmatter (everything between --- markers)
    $pattern = '/^---[\s\S]*?---\s*/';
    $cleanContent = preg_replace($pattern, '', $content);
    
    // Also remove the @php block if it's just for attributes
    $cleanContent = preg_replace('/^@php\s*\$attributes[^@]*@endphp\s*/s', '', $cleanContent);
    
    // Add proper Blade component structure
    $finalContent = "@props(['class' => ''])\n\n" . trim($cleanContent);
    
    return $finalContent;
}

function processDirectory($dir) {
    global $processedCount, $errorCount;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            
            try {
                $content = file_get_contents($filePath);
                
                // Check if file has Hugo frontmatter
                if (strpos($content, '---') === 0 || strpos($content, '@php') === 0) {
                    echo "Cleaning: " . str_replace('/var/www/api-gateway/resources/views/components/flowbite-pro/', '', $filePath) . "\n";
                    
                    $cleanContent = cleanComponent($filePath);
                    file_put_contents($filePath, $cleanContent);
                    $processedCount++;
                } else {
                    echo "Already clean: " . basename($filePath) . "\n";
                }
            } catch (Exception $e) {
                echo "Error processing $filePath: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
}

echo "🧹 Cleaning Flowbite Components\n";
echo "================================\n\n";

processDirectory($basePath);

echo "\n================================\n";
echo "✅ Processed: $processedCount files\n";
echo "❌ Errors: $errorCount files\n";
echo "\nComponents are now clean and ready for use!\n";