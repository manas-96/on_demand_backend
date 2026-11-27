@extends('layouts.admin')

@section('title', 'Customer Support Desk')

@section('content')
<div class="space-y-6">
    <!-- Top Header & Metrics -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                    Live Firestore Sync
                </span>
            </div>
            <h1 class="text-2xl font-bold text-white mt-1">Customer Support Center</h1>
            <p class="text-xs text-slate-400">Real-time customer inquiries, linked booking disputes, and live support chat channels.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="refreshTickets()" class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition">
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
                <span class="text-xs text-blue-400 font-semibold block uppercase tracking-wider">Processing</span>
                <span id="stat-processing" class="text-2xl font-extrabold text-blue-400">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center font-bold border border-blue-500/20">
                💬
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
            <div>
                <span class="text-xs text-emerald-400 font-semibold block uppercase tracking-wider">Done / Resolved</span>
                <span id="stat-done" class="text-2xl font-extrabold text-emerald-400">0</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold border border-emerald-500/20">
                ✓
            </div>
        </div>
    </div>

    <!-- Filter Tabs & Live Search Bar -->
    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Filter Tabs -->
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
                <span>Processing</span>
                <span id="tab-count-processing" class="px-1.5 py-0.5 rounded-full text-[10px] bg-blue-500/10 text-blue-400 font-bold border border-blue-500/20">0</span>
            </button>
            <button onclick="setFilter('done')" id="tab-done" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-2 text-slate-400 hover:text-white hover:bg-slate-800">
                <span>Done</span>
                <span id="tab-count-done" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">0</span>
            </button>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
            <select id="role-filter" onchange="renderTickets()" class="w-full sm:w-auto px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-amber-400 transition">
                <option value="all">All Requesters (Customers & Providers)</option>
                <option value="customer">Customers Only</option>
                <option value="provider">Providers Only</option>
            </select>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <input type="text" id="search-input" oninput="renderTickets()" placeholder="Search ticket #, booking #, party..."
                    class="w-full px-4 py-2 pl-9 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition">
                <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
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
                        <th class="px-6 py-4">Requester Details</th>
                        <th class="px-6 py-4">Subject & Last Message</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Updated At</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody id="tickets-table-body" class="divide-y divide-slate-800/80">
                    <!-- Loading Skeleton Placeholder -->
                    <tr id="loading-row">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <div class="inline-flex items-center space-x-2">
                                <svg class="animate-spin h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span class="text-xs font-semibold">Connecting to Firestore & streaming live support tickets...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Website support-form requests (stored in the database, not Firestore) -->
    <div id="web-requests" class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-white">Website Requests</h2>
                <p class="text-xs text-slate-400">Submitted through the public support form on the website.</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">{{ $webTickets->total() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950 text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800 tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Ticket</th>
                        <th class="px-6 py-4">Requester</th>
                        <th class="px-6 py-4">Subject</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Submitted</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($webTickets as $webTicket)
                        @php
                            $webStatusClasses = match($webTicket->status) {
                                'open' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'in_progress' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'resolved' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                default => 'bg-slate-800 text-slate-400 border-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4">
                                <span class="font-mono font-bold text-white text-xs">{{ $webTicket->ticket_number }}</span>
                                <div class="text-[10px] text-slate-500 mt-1 uppercase">{{ $webTicket->category }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">{{ $webTicket->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $webTicket->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-slate-200 truncate max-w-[260px]">{{ $webTicket->subject }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg border text-[10px] font-extrabold uppercase tracking-wider {{ $webStatusClasses }}">
                                    {{ str_replace('_', ' ', $webTicket->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400 whitespace-nowrap">
                                {{ $webTicket->created_at->inDisplayTimezone()->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.support.web.show', $webTicket) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs font-semibold text-amber-400 hover:bg-slate-700 transition">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-xs text-slate-500">No website support requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($webTickets->hasPages())
            <div class="px-6 py-4 border-t border-slate-800">
                {{ $webTickets->fragment('web-requests')->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Firebase SDKs -->
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-firestore-compat.js"></script>

<script>
    // Initialize Firebase
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

        unsubscribeListener = db.collection('support_tickets')
            .onSnapshot((snapshot) => {
                const tickets = [];
                snapshot.forEach((doc) => {
                    const data = doc.data();
                    const userRole = (data.user_role || (data.provider_id ? 'provider' : 'customer')).toLowerCase();
                    const userName = userRole === 'provider' 
                        ? (data.company_name || data.provider_name || 'Provider') 
                        : (data.customer_name || 'Customer');
                    const userContact = userRole === 'provider'
                        ? (data.provider_phone || data.provider_email || '')
                        : (data.customer_phone || data.customer_email || '');
                    const userEmail = userRole === 'provider' ? (data.provider_email || '') : (data.customer_email || '');

                    tickets.push({
                        id: doc.id,
                        ticket_number: data.ticket_number || ('TKT-' + doc.id.substring(0, 6).toUpperCase()),
                        user_role: userRole,
                        booking_id: data.booking_id || '',
                        booking_title: data.booking_title || 'Service Booking',
                        category_name: data.category_name || '',
                        customer_id: data.customer_id || data.provider_id || '',
                        customer_name: userName,
                        customer_phone: userContact,
                        customer_email: userEmail,
                        company_name: data.company_name || '',
                        provider_name: data.provider_name || '',
                        subject: data.subject || 'Support Request',
                        initial_message: data.initial_message || '',
                        last_message: data.last_message || data.initial_message || '',
                        last_message_by: data.last_message_by || 'user',
                        status: (data.status || 'pending').toLowerCase(),
                        priority: (data.priority || 'medium').toLowerCase(),
                        unread_by_admin: parseInt(data.unread_by_admin || 0),
                        created_at: data.created_at ? data.created_at.toDate() : new Date(),
                        updated_at: data.updated_at ? data.updated_at.toDate() : new Date(),
                    });
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
        
        // Reset tab styles
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

        // Apply Role Filter
        const roleFilter = document.getElementById('role-filter') ? document.getElementById('role-filter').value : 'all';
        if (roleFilter !== 'all') {
            filtered = filtered.filter(t => t.user_role === roleFilter);
        }

        // Apply Tab Filter
        if (currentFilter === 'pending') {
            filtered = filtered.filter(t => t.status === 'pending');
        } else if (currentFilter === 'processing') {
            filtered = filtered.filter(t => t.status === 'processing' || t.status === 'in_progress');
        } else if (currentFilter === 'done') {
            filtered = filtered.filter(t => t.status === 'done' || t.status === 'resolved' || t.status === 'closed');
        }

        // Apply Search Term
        if (searchTerm) {
            filtered = filtered.filter(t => 
                t.ticket_number.toLowerCase().includes(searchTerm) ||
                t.booking_id.toString().toLowerCase().includes(searchTerm) ||
                t.customer_name.toLowerCase().includes(searchTerm) ||
                (t.company_name && t.company_name.toLowerCase().includes(searchTerm)) ||
                t.customer_email.toLowerCase().includes(searchTerm) ||
                t.customer_phone.toLowerCase().includes(searchTerm) ||
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
                                ${searchTerm ? 'No tickets matching "' + searchTerm + '"' : 'There are no ' + currentFilter + ' tickets right now.'}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filtered.map(t => {
            const timeAgo = formatDateTime(t.updated_at);
            const chatUrl = `{{ url('/admin/support') }}/${t.id}`;
            const bookingUrl = t.booking_id ? `{{ url('/admin/requests') }}/${t.booking_id}` : '#';

            let statusBadge = '';
            if (t.status === 'done' || t.status === 'resolved' || t.status === 'closed') {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Resolved</span>`;
            } else if (t.status === 'processing' || t.status === 'in_progress') {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">Processing</span>`;
            } else {
                statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending</span>`;
            }

            let priorityBadge = '';
            if (t.priority === 'high') {
                priorityBadge = `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20 ml-2">High</span>`;
            }

            const unreadBadge = t.unread_by_admin > 0 
                ? `<span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-rose-600 text-white animate-pulse shadow-lg shadow-rose-600/30">${t.unread_by_admin} new</span>`
                : '';

            const isProvider = t.user_role === 'provider';
            const roleBadge = isProvider
                ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20 mb-1">🏢 Provider</span>`
                : `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-1">👤 Customer</span>`;

            const lastMessageSender = t.last_message_by === 'admin' 
                ? 'Support:' 
                : (isProvider ? 'Provider:' : 'Customer:');

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
                        ` : '<span class="text-slate-500 italic">None</span>'}
                        <div class="text-[11px] font-semibold text-slate-300 truncate max-w-[180px] mt-1">${t.booking_title}</div>
                        ${t.category_name ? `<div class="text-[10px] text-slate-500 truncate">${escapeHtml(t.category_name)}</div>` : ''}
                    </td>
                    <td class="px-6 py-4">
                        <div>${roleBadge}</div>
                        <div class="font-bold text-white">${escapeHtml(t.customer_name)}</div>
                        ${isProvider && t.provider_name && t.company_name ? `<div class="text-[10px] text-slate-400">${escapeHtml(t.provider_name)}</div>` : ''}
                        <div class="text-[11px] text-slate-400">${escapeHtml(t.customer_phone || t.customer_email || 'No contact')}</div>
                        ${t.customer_email && t.customer_phone ? `<div class="text-[10px] text-slate-500 truncate max-w-[160px]">${escapeHtml(t.customer_email)}</div>` : ''}
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <div class="font-bold text-slate-100 truncate flex items-center space-x-1.5">
                            <span>${escapeHtml(t.subject)}</span>
                            ${unreadBadge}
                        </div>
                        <div class="text-[11px] text-slate-400 truncate mt-0.5">
                            <span class="${t.last_message_by !== 'admin' ? 'text-amber-400/80 font-semibold' : 'text-slate-500'}">
                                ${lastMessageSender}
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

    // Start Real-Time Listener on Load
    document.addEventListener('DOMContentLoaded', () => {
        initTicketsSubscription();
    });
</script>
@endsection
