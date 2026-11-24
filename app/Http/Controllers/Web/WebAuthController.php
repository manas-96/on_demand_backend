<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // One email can have several accounts (one per role); the web panel only signs in admin or provider
        // accounts, preferring admin when both exist.
        $panelCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            fn (Builder $query) => $query
                ->whereIn('role', [UserRole::Admin->value, UserRole::Provider->value])
                ->orderByRaw('case when role = ? then 0 else 1 end', [UserRole::Admin->value]),
        ];

        if (! Auth::attempt($panelCredentials, $request->boolean('remember'))) {
            // Correct password but only customer/technician accounts: point them to the app.
            $mobileOnly = User::where('email', $credentials['email'])
                ->whereIn('role', [UserRole::Customer->value, UserRole::Technician->value])
                ->get()
                ->contains(fn (User $account) => Hash::check($credentials['password'], $account->password));

            return back()->withErrors([
                'email' => $mobileOnly
                    ? 'This web panel is for providers and admins. Please sign in with the Grab A Hand mobile app.'
                    : 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact support.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        // Unverified / unapproved providers are routed to onboarding by EnsureProviderIsOnboarded.
        return redirect()->intended('/provider');
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
