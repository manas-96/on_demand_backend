<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderSupportController extends Controller
{
    /**
     * Display real-time Provider Support & Helpdesk center from Firestore.
     */
    public function index(Request $request): View
    {
        $provider = $request->user();
        $profile = ProviderProfile::where('user_id', $provider->id)->first();
        $firebaseConfig = config('services.firebase');

        // Recent bookings associated with this provider (to link in support tickets)
        $recentBookings = ServiceRequest::where(function ($q) use ($provider) {
            $q->where('target_provider_id', $provider->id)
                ->orWhereHas('jobAssignments', function ($ja) use ($provider) {
                    $ja->where('provider_id', $provider->id);
                })
                ->orWhereHas('quotes', function ($qq) use ($provider) {
                    $qq->where('provider_id', $provider->id);
                });
        })
            ->with(['category', 'service'])
            ->latest()
            ->take(25)
            ->get();

        return view('provider.support.index', compact('provider', 'profile', 'firebaseConfig', 'recentBookings'));
    }

    /**
     * Display real-time 2-way live chat console for a specific Firestore ticket.
     */
    public function show(Request $request, string $id): View
    {
        $provider = $request->user();
        $profile = ProviderProfile::where('user_id', $provider->id)->first();
        $firebaseConfig = config('services.firebase');

        return view('provider.support.chat', [
            'ticketId' => $id,
            'provider' => $provider,
            'profile' => $profile,
            'firebaseConfig' => $firebaseConfig,
        ]);
    }
}
