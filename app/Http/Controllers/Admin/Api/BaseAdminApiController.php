<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseAdminApiController extends Controller
{
    use AuthorizesRequests {
        authorize as protected traitAuthorize;
    }
    
    /**
     * Override authorize to skip authorization for admin API
     */
    public function authorize($ability, $arguments = [])
    {
        // Skip all authorization checks for admin API
        return true;
    }
}