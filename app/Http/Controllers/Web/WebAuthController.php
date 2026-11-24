<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    /**
     * Display landing page with login option.
     */
    public function landing(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return $user->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('provider.dashboard');
        }

        return view('landing');
    }

    /**
     * Handle web login form submission and role-based redirect.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            $refusal = match (true) {
                ! $user->is_active => 'Your account has been deactivated. Please contact support.',
                ! $user->isAdmin() && ! $user->isProvider() => 'This web panel is for providers and admins. Please sign in with the Grab A Hand mobile app.',
                default => null,
            };

            if ($refusal) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors(['email' => $refusal])->onlyInput('email');
            }

            $request->session()->regenerate();

            if ($user->isAdmin()) {
                return redirect()->intended('/admin');
            }

            // Unverified / unapproved providers are routed to onboarding by EnsureProviderIsOnboarded.
            return redirect()->intended('/provider');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log out web user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
