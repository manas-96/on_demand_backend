<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provider panel gate: a provider must verify their email and be approved by an admin before using the panel.
 * Runs after EnsureUserIsProvider.
 */
class EnsureProviderIsOnboarded
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return $this->deny($request, 'provider.onboarding.verify-email', 'Please verify your email address first.');
        }

        if (! $user->providerProfile?->is_approved) {
            return $this->deny($request, 'provider.onboarding.pending', 'Your provider account is awaiting admin approval.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $route, string $message): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return redirect()->route($route);
    }
}
