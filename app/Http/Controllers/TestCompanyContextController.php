<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestCompanyContextController extends Controller
{
    public function test(Request $request)
    {
        $data = [
            'auth_check' => Auth::check(),
            'auth_user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'company_id' => Auth::user()->company_id
            ] : null,
            'app_has_company_id' => app()->has('current_company_id'),
            'current_company_id' => app()->has('current_company_id') ? app('current_company_id') : null,
            'company_context_source' => app()->has('company_context_source') ? app('company_context_source') : null,
            'session_company_id' => session('current_company_id'),
            'middleware_on_route' => $request->route() ? $request->route()->middleware() : [],
            'request_path' => $request->path(),
            'request_url' => $request->url(),
        ];
        
        return response()->json($data, 200, [], JSON_PRETTY_PRINT);
    }
}