<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReactAdminController extends Controller
{
    /**
     * Show the React admin login page
     */
    public function login()
    {
        // If already authenticated, redirect to admin
        if (auth()->check() && auth()->user()->hasRole(['admin', 'super-admin'])) {
            return redirect('/admin');
        }

        return view('admin.login-react');
    }

    /**
     * Show the React admin app
     */
    public function index()
    {
        return view('admin.react-app');
    }

    /**
     * Show the React admin portal
     */
    public function portal()
    {
        return view('admin.react-admin-portal');
    }
}