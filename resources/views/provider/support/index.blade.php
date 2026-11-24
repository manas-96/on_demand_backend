@extends('layouts.provider')

@section('title', 'Help & Support')

@section('content')
<div class="space-y-6">
    <!-- Top Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                    Live Platform Helpdesk
                </span>
            </div>
            <h1 class="text-2xl font-bold text-white mt-1">Provider Support & Helpdesk</h1>
            <p class="text-xs text-slate-400">Direct real-time communication channel with platform operations, dispute resolution, and payouts team.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="openCreateTicketModal()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 hover:brightness-110 transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Open New Ticket</span>
            </button>
            <button onclick="refreshTickets()" class="px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Refresh</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Total Tickets</span>
                <span id="stat-total" class="text-2xl font-extrabold text-white">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center font-bold">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs text-amber-400 font-semibold block uppercase tracking-wider">Pending</span>
                <span id="stat-pending" class="text-2xl font-extrabold text-amber-400">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold border border-amber-500/20">
                ⏳
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs text-blue-400 font-semibold block uppercase tracking-wider">In Progress</span>
                <span id="stat-processing" class="text-2xl font-extrabold text-blue-400">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold border border-blue-500/20">
                💬
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs text-emerald-400 font-semibold block uppercase tracking-wider">Resolved</span>
                <span id="stat-done" class="text-2xl font-extrabold text-emerald-400">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold border border-emerald-500/20">
                ✓
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Search Bar -->
    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0" id="status-tabs">
            <button onclick="setFilter('all')" id="tab-all" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 shadow-lg shadow-amber-500/20">
                <span>All Tickets</span>
                <span id="tab-count-all" class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-950/20 text-slate-950 font-black">0</span>
            </button>
            <button onclick="setFilter('pending')" id="tab-pending" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 text-slate-400 hover:text-white hover:bg-slate-800">
                <span>Pending</span>
                <span id="tab-count-pending" class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500/10 text-amber-400 font-bold border border-amber-500/20">0</span>
            </button>
            <button onclick="setFilter('processing')" id="tab-processing" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 text-slate-400 hover:text-white hover:bg-slate-800">
                <span>In Progress</span>
                <span id="tab-count-processing" class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-500/10 text-blue-400 font-bold border border-blue-500/20">0</span>
            </button>
            <button onclick="setFilter('done')" id="tab-done" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 text-slate-400 hover:text-white hover:bg-slate-800">
                <span>Resolved</span>
                <span id="tab-count-done" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">0</span>
            </button>
        </div>

        <div class="relative w-full md:w-80">
            <input type="text" id="search-input" oninput="renderTickets()" placeholder="Search ticket #, booking #, subject..."
                class="w-full px-4 py-2 pl-9 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Live Tickets Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950 text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800 tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Ticket & Priority</th>
                        <th class="px-6 py-4">Linked Booking</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Subject & Latest Message</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Last Updated</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="tickets-table-body" class="divide-y divide-slate-800/80">
                    <tr id="loading-row">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <div class="inline-flex items-center space-x-2">
                                <svg class="animate-spin h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span class="text-xs font-semibold">Connecting to Firestore & streaming support tickets...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create Support Ticket -->
<div id="create-ticket-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="relative w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-lg font-bold text-white">Create Support Inquiry</h3>
                <p class="text-xs text-slate-400">Our operations team will respond directly via live chat.</p>
            </div>
            <button onclick="closeCreateTicketModal()" class="w-8 h-8 rounded-xl bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition">
                ✕
            </button>
        </div>

        <form id="create-ticket-form" onsubmit="submitNewTicket(event)" class="space-y-4">
            <!-- Category -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Inquiry Category <span class="text-amber-400">*</span></label>
                <select id="modal-category" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400 transition">
                    <option value="Payout & Earnings Issue">Payout & Earnings Issue</option>
                    <option value="Booking & Customer Dispute">Booking & Customer Dispute</option>
                    <option value="Commission & Pricing Query">Commission & Pricing Query</option>
                    <option value="Technician Verification & Account">Technician Verification & Account</option>
                    <option value="General Platform Support">General Platform Support</option>
                </select>
            </div>

            <!-- Linked Booking Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Linked Booking / Job (Optional)</label>
                <select id="modal-booking" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400 transition">
                    <option value="">-- None / General Provider Inquiry --</option>
                    @foreach($recentBookings as $b)
                        <option value="{{ $b->id }}" data-title="{{ $b->service->name ?? $b->category->name ?? 'Service Booking' }}" data-category="{{ $b->category->name ?? 'General' }}">
                            #{{ $b->request_number ?? $b->id }} - {{ $b->service->name ?? $b->category->name ?? 'Booking' }} ({{ ucfirst($b->status?->value ?? $b->status) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Priority</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="modal-priority" value="low" class="sr-only peer">
                        <div class="p-2.5 rounded-xl border border-slate-800 text-center text-xs font-bold text-slate-400 peer-checked:bg-slate-800 peer-checked:border-slate-600 peer-checked:text-white transition">
                            Low
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="modal-priority" value="medium" checked class="sr-only peer">
                        <div class="p-2.5 rounded-xl border border-slate-800 text-center text-xs font-bold text-slate-400 peer-checked:bg-amber-500/20 peer-checked:border-amber-500/40 peer-checked:text-amber-400 transition">
                            Medium
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="modal-priority" value="high" class="sr-only peer">
                        <div class="p-2.5 rounded-xl border border-slate-800 text-center text-xs font-bold text-slate-400 peer-checked:bg-rose-500/20 peer-checked:border-rose-500/40 peer-checked:text-rose-400 transition">
                            High
                        </div>
                    </label>
                </div>
            </div>

            <!-- Subject with Quick Suggestion Chips -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Subject <span class="text-amber-400">*</span></label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <button type="button" onclick="setPresetSubject('Payout Delay on Completed Booking')" class="px-2 py-0.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">💰 Payout delay</button>
                    <button type="button" onclick="setPresetSubject('Customer Dispute / Unresponsive')" class="px-2 py-0.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">⚠️ Customer dispute</button>
                    <button type="button" onclick="setPresetSubject('Commission Rate Clarification')" class="px-2 py-0.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">📊 Commission query</button>
                    <button type="button" onclick="setPresetSubject('Technician Verification Delay')" class="px-2 py-0.5 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-semibold border border-slate-700">🔧 Technician issue</button>
                </div>
                <input type="text" id="modal-subject" required placeholder="E.g. Delayed bank settlement for booking #..."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Initial Message -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Detailed Description <span class="text-amber-400">*</span></label>
                <textarea id="modal-message" required rows="4" placeholder="Describe the issue in detail so our operations team can quickly help you..."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeCreateTicketModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition">
                    Cancel
                </button>
                <button type="submit" id="btn-submit-ticket" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 hover:brightness-110 transition flex items-center space-x-2">
                    <span>Submit & Open Chat</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Firebase SDKs -->
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-firestore-compat.js"></script>

<script>
    const currentProviderId = parseInt("{{ $provider->id }}");
    const currentProviderName = "{{ $provider->name }}";
    const currentCompanyName = "{{ $profile->company_name ?? $provider->name }}";
    const currentProviderEmail = "{{ $provider->email }}";
    const currentProviderPhone = "{{ $provider->phone ?? '' }}";

    const firebaseConfig = {
        apiKey: "{{ $firebaseConfig['api_key'] ?? '' }}",
        projectId: "{{ $firebaseConfig['project_id'] ?? 'on-demand-3834d' }}",
        messagingSenderId: "{{ $firebaseConfig['messaging_sender_id'] ?? '' }}",
        appId: "{{ $firebaseConfig['app_id'] ?? '' }}",
        storageBucket: "{{ $firebaseConfig['storage_bucket'] ?? '' }}"
    };

    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }
    const db = firebase.firestore();

    let allTickets = [];
    let currentFilter = 'all';
    let unsubscribeListener = null;

    function initTicketsSubscription() {
        if (unsubscribeListener) unsubscribeListener();

        // Listen for all tickets belonging to this provider
        unsubscribeListener = db.collection('support_tickets')
            .onSnapshot((snapshot) => {
                const tickets = [];
                snapshot.forEach((doc) => {
                    const data = doc.data();
                    const pId = parseInt(data.provider_id || data.user_id || 0);

                    // Filter only tickets belonging to this provider
                    if (pId === currentProviderId || (data.user_role === 'provider' && data.customer_id === currentProviderId)) {
                        tickets.push({
                            id: doc.id,
                            ticket_number: data.ticket_number || ('TKT-' + doc.id.substring(0, 6).toUpperCase()),
                            booking_id: data.booking_id || '',
                            booking_title: data.booking_title || '',
                            category_name: data.category_name || 'General Support',
                            subject: data.subject || 'Support Request',
                            initial_message: data.initial_message || '',
                            last_message: data.last_message || data.initial_message || '',
                            last_message_by: data.last_message_by || 'provider',
                            status: (data.status || 'pending').toLowerCase(),
                            priority: (data.priority || 'medium').toLowerCase(),
                            unread_by_provider: parseInt(data.unread_by_provider || 0),
                            created_at: data.created_at ? data.created_at.toDate() : new Date(),
                            updated_at: data.updated_at ? data.updated_at.toDate() : new Date(),
                        });
                    }
                });

                // Sort newest updated first
                tickets.sort((a, b) => b.updated_at - a.updated_at);
                allTickets = tickets;

                updateCounts();
                renderTickets();
            }, (error) => {
                console.error("Firestore error:", error);
                document.getElementById('tickets-table-body').innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-rose-400 text-xs">
                            ⚠️ Failed to connect to Firestore support tickets: ${error.message}
                        </td>
                    </tr>
                `;
            });
    }

    function updateCounts() {
        const total = allTickets.length;
        const pending = allTickets.filter(t => t.status === 'pending').length;
        const processing = allTickets.filter(t => t.status === 'processing' || t.status === 'in_progress').length;
        const done = allTickets.filter(t => t.status === 'done' || t.status === 'resolved' || t.status === 'closed').length;

        document.getElementById('stat-total').innerText = total;
        document.getElementById('stat-pending').innerText = pending;
        document.getElementById('stat-processing').innerText = processing;
        document.getElementById('stat-done').innerText = done;

        document.getElementById('tab-count-all').innerText = total;
        document.getElementById('tab-count-pending').innerText = pending;
        document.getElementById('tab-count-processing').innerText = processing;
        document.getElementById('tab-count-done').innerText = done;
    }

    function setFilter(filter) {
        currentFilter = filter;
        
        ['all', 'pending', 'processing', 'done'].forEach(f => {
            const el = document.getElementById(`tab-${f}`);
            if (f === filter) {
                el.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 shadow-lg shadow-amber-500/20";
            } else {
                el.className = "px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 text-slate-400 hover:text-white hover:bg-slate-800";
            }
        });

        renderTickets();
    }

    function renderTickets() {
        const tbody = document.getElementById('tickets-table-body');
        const searchTerm = document.getElementById('search-input').value.trim().toLowerCase();

        let filtered = allTickets;

        if (currentFilter === 'pending') {
            filtered = filtered.filter(t => t.status === 'pending');
        } else if (currentFilter === 'processing') {
            filtered = filtered.filter(t => t.status === 'processing' || t.status === 'in_progress');
        } else if (currentFilter === 'done') {
            filtered = filtered.filter(t => t.status === 'done' || t.status === 'resolved' || t.status === 'closed');
        }

        if (searchTerm) {
            filtered = filtered.filter(t => 
                t.ticket_number.toLowerCase().includes(searchTerm) ||
                t.booking_id.toString().toLowerCase().includes(searchTerm) ||
                t.category_name.toLowerCase().includes(searchTerm) ||
                t.subject.toLowerCase().includes(searchTerm) ||
                t.last_message.toLowerCase().includes(searchTerm)
            );
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                        <div class="max-w-sm mx-auto space-y-2">
                            <div class="text-3xl">📭</div>
                            <div class="text-sm font-bold text-white">No tickets found</div>
                            <div class="text-xs text-slate-500">
                                ${searchTerm ? 'No tickets matching "' + searchTerm + '"' : 'You have no ' + currentFilter + ' support tickets right now.'}
                            </div>
                            <div class="pt-2">
                                <button onclick="openCreateTicketModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-bold border border-slate-700 transition">
                                    + Open a Support Ticket
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(t => {
            const timeAgo = formatDateTime(t.updated_at);
            const chatUrl = `{{ url('/provider/support') }}/${t.id}`;
            const bookingUrl = t.booking_id ? `{{ url('/provider/requests') }}/${t.booking_id}` : '#';

            let statusBadge = '';
            if (t.status === 'done' || t.status === 'resolved' || t.status === 'closed') {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Resolved</span>`;
            } else if (t.status === 'processing' || t.status === 'in_progress') {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">In Progress</span>`;
            } else {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending</span>`;
            }

            let priorityBadge = '';
            if (t.priority === 'high') {
                priorityBadge = `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20 ml-2">High</span>`;
            }

            const unreadBadge = t.unread_by_provider > 0 
                ? `<span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-600 text-white animate-pulse shadow-lg shadow-rose-600/30">${t.unread_by_provider} new</span>`
                : '';

            return `
                <tr class="hover:bg-slate-800/40 text-xs transition duration-150">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <span class="font-mono font-bold text-white text-xs">${t.ticket_number}</span>
                            ${priorityBadge}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1 font-mono">ID: ${t.id.substring(0, 8)}...</div>
                    </td>
                    <td class="px-6 py-4">
                        ${t.booking_id ? `
                            <a href="${bookingUrl}" target="_blank" class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20 transition">
                                <span>#${t.booking_id}</span>
                                <span class="ml-1 text-[9px]">↗</span>
                            </a>
                            <div class="text-[11px] font-semibold text-slate-300 truncate max-w-[160px] mt-1">${escapeHtml(t.booking_title)}</div>
                        ` : '<span class="text-slate-500 italic">None</span>'}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 font-semibold text-[11px] border border-slate-700">
                            ${escapeHtml(t.category_name)}
                        </span>
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <div class="font-bold text-slate-100 truncate flex items-center space-x-1.5">
                            <span>${escapeHtml(t.subject)}</span>
                            ${unreadBadge}
                        </div>
                        <div class="text-[11px] text-slate-400 truncate mt-0.5">
                            <span class="${t.last_message_by === 'admin' ? 'text-amber-400 font-semibold' : 'text-slate-500'}">
                                ${t.last_message_by === 'admin' ? 'Support:' : 'You:'}
                            </span>
                            ${escapeHtml(t.last_message)}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${statusBadge}
                    </td>
                    <td class="px-6 py-4 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                        ${timeAgo}
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <a href="${chatUrl}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold transition shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span>Live Chat</span>
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Modal Control
    function openCreateTicketModal() {
        document.getElementById('create-ticket-modal').classList.remove('hidden');
    }

    function closeCreateTicketModal() {
        document.getElementById('create-ticket-modal').classList.add('hidden');
    }

    function setPresetSubject(subj) {
        document.getElementById('modal-subject').value = subj;
    }

    async function submitNewTicket(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit-ticket');
        btn.disabled = true;
        btn.classList.add('opacity-50');

        try {
            const category = document.getElementById('modal-category').value;
            const bookingSelect = document.getElementById('modal-booking');
            const bookingId = bookingSelect.value || null;
            const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
            const bookingTitle = bookingId ? (selectedOption.getAttribute('data-title') || 'Service Booking') : '';
            const priority = document.querySelector('input[name="modal-priority"]:checked').value;
            const subject = document.getElementById('modal-subject').value.trim();
            const message = document.getElementById('modal-message').value.trim();

            const ticketNumber = 'TKT-' + Math.floor(100000 + Math.random() * 900000);
            const docRef = db.collection('support_tickets').doc();

            // 1. Write the main Ticket Document
            await docRef.set({
                ticket_number: ticketNumber,
                user_role: 'provider',
                provider_id: currentProviderId,
                provider_name: currentProviderName,
                company_name: currentCompanyName,
                provider_email: currentProviderEmail,
                provider_phone: currentProviderPhone,
                booking_id: bookingId,
                booking_title: bookingTitle,
                category_name: category,
                subject: subject,
                initial_message: message,
                last_message: message,
                last_message_by: 'provider',
                status: 'pending',
                priority: priority,
                unread_by_admin: 1,
                unread_by_provider: 0,
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                updated_at: firebase.firestore.FieldValue.serverTimestamp()
            });

            // 2. Write the initial message in subcollection
            await docRef.collection('messages').add({
                sender_id: String(currentProviderId),
                sender_name: currentCompanyName || currentProviderName,
                sender_role: 'provider',
                message: message,
                attachments: [],
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                seen_at: null
            });

            // Redirect directly to the live chat room
            window.location.href = `{{ url('/provider/support') }}/${docRef.id}`;
        } catch (err) {
            console.error("Failed to create ticket:", err);
            alert("Failed to submit support ticket: " + err.message);
            btn.disabled = false;
            btn.classList.remove('opacity-50');
        }
    }

    function formatDateTime(d) {
        if (!d) return '';
        const date = new Date(d);
        const options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return date.toLocaleDateString('en-US', options);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function refreshTickets() {
        initTicketsSubscription();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initTicketsSubscription();
    });
</script>
@endsection
