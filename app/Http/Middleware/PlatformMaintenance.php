<?php
namespace App\Http\Middleware;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class PlatformMaintenance
{
    public function handle(Request $request, Closure $next): Response {
        // Keep staff recovery and signed provider callbacks available during maintenance.
        if ($request->is('api/v1/admin/*') || $request->is('api/v1/webhooks/providers/*')) return $next($request);
        if (SystemSetting::getBoolean('maintenance_mode')) {
            return response()->json(['success' => false, 'message' => 'MGS API is temporarily under maintenance.'], 503, ['Retry-After' => '300']);
        }
        return $next($request);
    }
}
