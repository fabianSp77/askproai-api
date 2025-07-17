<?php

namespace App\Support;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Js;

class InertiaFacade
{
    protected static $sharedData = [];
    protected static $version = null;
    
    public static function render($component, $props = [])
    {
        $page = [
            'component' => $component,
            'props' => array_merge(self::$sharedData, $props),
            'url' => request()->getRequestUri(),
            'version' => self::$version,
        ];
        
        if (request()->header('X-Inertia')) {
            return response()->json($page)
                ->withHeaders([
                    'Vary' => 'Accept',
                    'X-Inertia' => 'true',
                ]);
        }
        
        return View::make('app', [
            'page' => $page,
        ]);
    }
    
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$sharedData = array_merge(self::$sharedData, $key);
        } else {
            self::$sharedData[$key] = $value;
        }
    }
    
    public static function version($version)
    {
        self::$version = $version;
    }
}