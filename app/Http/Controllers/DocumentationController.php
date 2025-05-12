<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DocumentationController extends Controller
{
    public function show($page = 'index.html')
    {
        $filePath = public_path("admin/documentation/$page");

        if (!File::exists($filePath)) {
            abort(404, 'Dokument nicht gefunden');
        }

        return response()->file($filePath);
    }
}
