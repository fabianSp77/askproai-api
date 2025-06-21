<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    /**
     * Display the privacy policy page
     */
    public function privacy()
    {
        return view('legal.privacy');
    }

    /**
     * Display the cookie policy page
     */
    public function cookiePolicy()
    {
        return view('legal.cookie-policy');
    }

    /**
     * Display the terms of service page
     */
    public function terms()
    {
        return view('legal.terms');
    }

    /**
     * Display the impressum page (German legal requirement)
     */
    public function impressum()
    {
        return view('legal.impressum');
    }
}