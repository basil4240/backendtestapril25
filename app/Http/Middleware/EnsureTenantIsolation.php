<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsolation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // For routes with company_id parameter, ensure user belongs to that company
        if ($request->route('company') && $request->route('company') != $user->company_id) {
            return response()->json(['message' => 'Unauthorized access to another company\'s data'], 403);
        }

        // For routes with user_id parameter, ensure the user belongs to the same company
        if ($request->route('user')) {
            $requestedUserId = $request->route('user');
            // If the user is trying to access their own info, allow it
            if ($requestedUserId == $user->id) {
                return $next($request);
            }
            
            // Otherwise check if the requested user belongs to the same company
            $requestedUser = \App\Models\User::find($requestedUserId);
            if (!$requestedUser || $requestedUser->company_id != $user->company_id) {
                return response()->json(['message' => 'Unauthorized access to user from another company'], 403);
            }
        }

        return $next($request);
    }
}