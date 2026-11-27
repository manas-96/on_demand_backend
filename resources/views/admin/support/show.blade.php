@extends('layouts.admin')

@section('title', 'Support Ticket ' . $ticket->ticket_number)

@section('content')
<div class="space-y-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.support.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">← Back to Support Tickets</a>
            <h1 class="text-2xl font-bold text-white">Support Ticket: {{ $ticket->ticket_number }}</h1>
            <p class="text-xs text-slate-400">Detailed inquiry view and admin status update panel.</p>
        </div>
        <div>
            @php
                $statusClasses = match($ticket->status) {
                    'open' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                    'in_progress' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
                    'resolved' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                    'closed' => 'bg-slate-800 text-slate-400 border-slate-700',
                    default => 'bg-slate-800 text-slate-300 border-slate-700',
                };
            @endphp
            <span class="px-3 py-1.5 rounded-xl border text-xs font-extrabold uppercase tracking-wider {{ $statusClasses }}">
                Status: {{ str_replace('_', ' ', $ticket->status) }}
            </span>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Ticket Content & Submitter Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ticket Info Card -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <span class="px-2.5 py-1 rounded bg-slate-800 text-amber-400 border border-amber-500/20 text-xs font-bold uppercase tracking-wider">
                        Category: {{ $ticket->category }}
                    </span>
                    <span class="text-xs font-mono text-slate-500">Submitted: {{ $ticket->created_at->inDisplayTimezone()->format('M d, Y H:i') }}</span>
                </div>

                <div class="space-y-2">
                    <span class="text-xs text-slate-500 block uppercase tracking-wider font-semibold">Subject Title</span>
                    <h2 class="text-xl font-bold text-white leading-snug">{{ $ticket->subject }}</h2>
                </div>

                <div class="space-y-2 pt-2">
                    <span class="text-xs text-slate-500 block uppercase tracking-wider font-semibold">Detailed Message</span>
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-sm leading-relaxed whitespace-pre-line">
                        {{ $ticket->message }}
                    </div>
                </div>
            </div>

            <!-- Submitter Information -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3 text-xs">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">Submitter Contact Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div><span class="text-slate-500">Name:</span> <strong class="text-white">{{ $ticket->name }}</strong></div>
                    <div><span class="text-slate-500">Email:</span> <a href="mailto:{{ $ticket->email }}" class="text-amber-400 hover:underline">{{ $ticket->email }}</a></div>
                    <div><span class="text-slate-500">Phone:</span> <span class="text-slate-300">{{ $ticket->phone ?? 'Not provided' }}</span></div>
                    <div><span class="text-slate-500">Linked Account:</span> <strong class="text-indigo-400">{{ $ticket->user?->name ?? 'Guest Visitor' }}</strong></div>
                </div>
            </div>
        </div>

        <!-- Right: Admin Status Update & Notes -->
        <div class="space-y-6">
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Update Ticket Status & Notes</h2>
                
                <form method="POST" action="{{ route('admin.support.web.update-status', $ticket) }}" class="space-y-4">
                    @csrf

                    @if($errors->any())
                        <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-400 space-y-1">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label for="status" class="block text-xs font-medium text-slate-300 mb-1">Target Ticket Status</label>
                        <select id="status" name="status" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400 transition">
                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>OPEN</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>IN PROGRESS</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>RESOLVED</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>CLOSED</option>
                        </select>
                    </div>

                    <div>
                        <label for="admin_notes" class="block text-xs font-medium text-slate-300 mb-1">Admin Internal Notes / Resolution</label>
                        <textarea id="admin_notes" name="admin_notes" rows="6" placeholder="Add administrative resolution notes or internal follow-up comments..."
                                  class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 transition">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                        Update Support Ticket
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
