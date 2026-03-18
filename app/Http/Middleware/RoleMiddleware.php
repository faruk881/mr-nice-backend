<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Get the user and role name
        $user = $request->user();
        $role = $role;
        
        // Check user role
        if ($user && $user->currentAccessToken()?->can('role:' . $role)) {
            return $next($request);
        }

        // Return the message
        return apiError('Unauthorized. '.ucfirst($role).'s only.', 403, ['code'=>'UNAUTHORIZED']);
    }
}
