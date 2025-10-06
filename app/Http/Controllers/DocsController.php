<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocsController extends Controller
{
    /**
     * Show the documentation index page
     */
    public function index()
    {
        $indexPath = storage_path('app/private/docs/index.html');

        if (!file_exists($indexPath)) {
            abort(404, 'Documentation index not found');
        }

        return response()->file($indexPath);
    }

    /**
     * Serve a markdown documentation file
     */
    public function show(Request $request, $path)
    {
        // Sanitize path to prevent directory traversal
        $path = str_replace(['..', '\\'], '', $path);

        $filePath = storage_path('app/private/docs/claudedocs/' . $path);

        if (!file_exists($filePath)) {
            abort(404, 'Documentation file not found');
        }

        // Check file extension
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($extension, ['md', 'markdown', 'txt'])) {
            abort(403, 'Invalid file type');
        }

        return response()->file($filePath, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
