<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)

    {
        // Check if the 'Authorization' header is present in the request
        if ($request->header('Authorization')) {
            // Get the bearer token from the 'Authorization' header
            $bearerToken = $request->bearerToken();

            // Find the user with a matching 'remember_token'
            $user = User::where('remember_token', $bearerToken)->first();
            if ($user) {

                Auth::login($user);
                // return response()->json(['user' => $user], 200);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Continue processing the request
        return $next($request);
    }
}
