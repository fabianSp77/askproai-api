<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json([
            'companies' => []
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'company' => null
        ]);
    }
}