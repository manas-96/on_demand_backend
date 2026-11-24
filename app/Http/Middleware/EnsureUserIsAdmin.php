<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized admin action.'], 403);
            }

            abort(403, 'Unauthorized access to Admin Web Panel.');
        }

        // A deactivated account loses access immediately, even mid-session.
        if (! $request->user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Your account is deactivated.'], 403);
            }

            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        return $next($request);
    }
}
