<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            return response()->json(['success' => false, 'message' => 'A user login session is required.'], 403);
        }

        if ($request->user()->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'This user account is not active.'], 403);
        }

        return $next($request);
    }
}
