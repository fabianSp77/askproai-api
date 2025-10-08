<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class RestrictToInternalNetwork
{
    public function handle(Request $request, Closure $next)
    {
        $allowed = config('admin.allowed', []) ?: [
            '127.0.0.1/8','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16',
        ];

        // Echte Client-IP bevorzugen (Cloudflare / Proxy)
        $ip = $request->headers->get('CF-Connecting-IP')
            ?: ($request->headers->get('X-Forwarded-For')
                ? trim(explode(',', $request->headers->get('X-Forwarded-For'))[0])
                : $request->ip());

        if (!IpUtils::checkIp($ip, $allowed)) {
            Log::warning('Admin access blocked - external IP', [
                'ip'=>$ip,'user_agent'=>$request->userAgent(),'path'=>$request->path(),'method'=>$request->method()
            ]);
            return $request->expectsJson()
                ? response()->json(['message'=>'Not Found'], 404)
                : response('', 404);
        }

        return $next($request);
    }
}
