<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'V2 Call API']);
    }
}