<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

class LivewireController extends Controller
{
    public function update(Request $request)
    {
        // Forward to Livewire's HandleRequests
        $handler = app(HandleRequests::class);
        
        // Call the __invoke method directly
        return $handler($request);
    }
}