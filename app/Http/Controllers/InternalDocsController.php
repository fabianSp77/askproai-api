<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class InternalDocsController extends Controller
{
    /**
     * Directory where internal docs are stored
     */
    private string $docsPath;

    public function __construct()
    {
        $this->docsPath = storage_path('app/internal-docs');
    }

    /**
     * Show the documentation index page
     */
    public function index(Request $request)
    {
        $files = $this->getDocumentFiles();
        $categories = $this->categorizeFiles($files);

        return view('internal-docs.index', [
            'categories' => $categories,
            'totalFiles' => count($files),
        ]);
    }

    /**
     * Serve a specific documentation file
     */
    public function show(Request $request, string $filename)
    {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filepath = $this->docsPath . '/' . $filename;

        if (!File::exists($filepath)) {
            abort(404, 'Document not found');
        }

        // Only allow HTML files
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'html') {
            abort(403, 'Invalid file type');
        }

        return response()->file($filepath, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Get all documentation files
     */
    private function getDocumentFiles(): array
    {
        if (!File::isDirectory($this->docsPath)) {
            return [];
        }

        $files = File::files($this->docsPath);
        $result = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'html') {
                $result[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'modified' => date('Y-m-d H:i', $file->getMTime()),
                    'title' => $this->extractTitle($file->getFilename()),
                ];
            }
        }

        // Sort by modified date descending
        usort($result, fn($a, $b) => strcmp($b['modified'], $a['modified']));

        return $result;
    }

    /**
     * Categorize files by prefix/type
     */
    private function categorizeFiles(array $files): array
    {
        $categories = [
            'Load Testing' => [],
            'Agent Documentation' => [],
            'API Tester' => [],
            'Appointments' => [],
            'Email Templates' => [],
            'System Documentation' => [],
            'Conversation Flows' => [],
            'Other' => [],
        ];

        foreach ($files as $file) {
            $name = strtolower($file['name']);

            if (str_contains($name, 'load-test')) {
                $categories['Load Testing'][] = $file;
            } elseif (str_contains($name, 'agent') || str_contains($name, 'AGENT')) {
                $categories['Agent Documentation'][] = $file;
            } elseif (str_contains($name, 'api-tester') || str_contains($name, 'backend-api')) {
                $categories['API Tester'][] = $file;
            } elseif (str_contains($name, 'appointment')) {
                $categories['Appointments'][] = $file;
            } elseif (str_contains($name, 'email')) {
                $categories['Email Templates'][] = $file;
            } elseif (str_contains($name, 'documentation') || str_contains($name, 'DOCUMENTATION') || str_contains($name, 'system')) {
                $categories['System Documentation'][] = $file;
            } elseif (str_contains($name, 'conversation') || str_contains($name, 'flow')) {
                $categories['Conversation Flows'][] = $file;
            } else {
                $categories['Other'][] = $file;
            }
        }

        // Remove empty categories
        return array_filter($categories, fn($items) => !empty($items));
    }

    /**
     * Extract a readable title from filename
     */
    private function extractTitle(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return ucwords($name);
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
