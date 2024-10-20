<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminCategory
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated and has 'admin' as their category
        if (Auth::check() && Auth::user()->category === 'admin') {
            return $next($request);  // Allow access if the user is an admin
        }

        // Optionally, redirect or return an error if the user is not an admin
        return redirect('/')->with('error', 'You do not have access to this section.');
    }
}
