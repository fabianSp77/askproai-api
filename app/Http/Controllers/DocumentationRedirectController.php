<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationRedirectController extends Controller
{
    /**
     * Redirect all documentation requests to the main MkDocs location
     */
    public function redirect(Request $request)
    {
        // Get the path after /documentation/ or /docs/
        $path = $request->path();
        $path = preg_replace('/^(documentation|docs)\//', '', $path);
        
        // Special case for index
        if (empty($path) || $path === 'index.html') {
            return redirect('/mkdocs/', 301);
        }
        
        // Redirect to MkDocs with the same path
        return redirect('/mkdocs/' . $path, 301);
    }
    
    /**
     * Show a landing page with links to all documentation
     */
    public function index()
    {
        return view('documentation.index');
    }
}