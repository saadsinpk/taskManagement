<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\RoleHasPermission;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user->id !== 1) {
            $role = $user->role;
            $path = $request->path();
            $pathWithoutId = preg_replace('/\/\d+$/', '', $path);
            $parts = explode('/', $pathWithoutId);
            $endpoint = end($parts);
            $roles = RoleHasPermission::where('role_id', $role)
                ->where('title', $endpoint)
                ->first();
            if (!$roles) {
                return response()->json(['message' => 'You do not have access'], 403);
            }
        }

        return $next($request);
    }
}
