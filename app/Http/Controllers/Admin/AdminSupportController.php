<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $webTickets = SupportTicket::query()
            ->latest()
            ->paginate(15, pageName: 'web_page')
            ->withQueryString();

        return view('admin.support.index', compact('firebaseConfig', 'adminUser', 'webTickets'));
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
     * Show a ticket submitted through the public website support form.
     */
    public function showWebTicket(SupportTicket $supportTicket): View
    {
        return view('admin.support.show', ['ticket' => $supportTicket->load('user')]);
    }

    /**
     * Update the status and internal notes of a website support ticket.
     */
    public function updateWebTicketStatus(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(SupportTicket::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $supportTicket->update($validated);

        return redirect()
            ->route('admin.support.web.show', $supportTicket)
            ->with('success', "Ticket {$supportTicket->ticket_number} updated.");
    }
}
