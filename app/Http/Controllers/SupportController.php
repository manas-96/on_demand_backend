<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    /**
     * Display public support center web page.
     */
    public function show(): View
    {
        return view('support');
    }

    /**
     * Store public support ticket inquiry (strictly validated & parameterized to prevent SQL Injection).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'category' => ['required', 'string', 'in:general,booking,payment,account,technical'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // PDO Prepared Statements via Eloquent mass assignment guarantees immunity to SQL injection
        $ticket = SupportTicket::create([
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'user_id' => auth()->id(),
            'name' => strip_tags(trim($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
            'category' => $validated['category'],
            'subject' => strip_tags(trim($validated['subject'])),
            'message' => strip_tags(trim($validated['message'])),
            'status' => 'open',
        ]);

        return back()->with('success', "Your support request has been submitted successfully! Tracking Number: {$ticket->ticket_number}. Our support team will get back to you shortly.");
    }

    /**
     * Public API endpoint for mobile client support submissions.
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'category' => ['required', 'string', 'in:general,booking,payment,account,technical'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => SupportTicket::generateTicketNumber(),
            'user_id' => auth('sanctum')->id(),
            'name' => strip_tags(trim($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
            'category' => $validated['category'],
            'subject' => strip_tags(trim($validated['subject'])),
            'message' => strip_tags(trim($validated['message'])),
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support request submitted successfully.',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
