<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSupportController extends Controller
{
    /**
     * Display real-time listing of customer support tickets from Firestore.
     */
    public function index(Request $request): View
    {
        $firebaseConfig = config('services.firebase');
        $adminUser = auth()->user();

        return view('admin.support.index', compact('firebaseConfig', 'adminUser'));
    }

    /**
     * Display live chat console and ticket management interface for a specific Firestore ticket.
     */
    public function show(string $id): View
    {
        $firebaseConfig = config('services.firebase');
        $adminUser = auth()->user();

        return view('admin.support.chat', [
            'ticketId' => $id,
            'firebaseConfig' => $firebaseConfig,
            'adminUser' => $adminUser,
        ]);
    }

    /**
     * Legacy or fallback status update route.
     */
    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        return back()->with('success', 'Ticket status updated.');
    }
}
