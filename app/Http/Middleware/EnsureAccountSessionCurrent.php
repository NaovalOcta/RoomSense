<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountSessionCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Existing sessions receive a version on their first request after deployment.
        $version = (int) $request->session()->get('auth_session_version', $user->auth_session_version);
        if ($version !== (int) $user->auth_session_version) {
            return $this->revoke($request);
        }
        $request->session()->put('auth_session_version', $version);

        $response = $next($request);

        // A request already in progress must not restore a session revoked on another device.
        if (Auth::check() && $version !== (int) $user->fresh()?->auth_session_version) {
            return $this->revoke($request);
        }

        return $response;
    }

    private function revoke(Request $request): Response
    {
        Auth::guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => 'Silakan login kembali.'], 401)
            : redirect()->route('login');
    }
}
