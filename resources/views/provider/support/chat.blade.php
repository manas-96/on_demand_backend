@extends('layouts.provider')

@section('title', 'Support Chat - ' . $ticketId)

@section('content')
<div class="space-y-6">
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('provider.support.index') }}" class="text-xs text-amber-400 hover:underline mb-1 inline-flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Help & Support Registry</span>
            </a>
            <div class="flex items-center space-x-3 mt-1">
                <h1 id="header-ticket-number" class="text-2xl font-mono font-bold text-white">Ticket Loading...</h1>
                <span id="header-priority-badge" class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-800 text-slate-300">MEDIUM</span>
            </div>
            <p id="header-subject" class="text-xs text-slate-400 mt-0.5">Live dedicated communication channel with platform operations</p>
        </div>

        <div id="live-status-pill" class="flex items-center">
            <!-- Rendered via JS -->
        </div>
    </div>

    <!-- Main Chat & Context Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Left Sidebar: Ticket & Booking Info -->
        <div class="space-y-5">
            <!-- Linked Booking Info Card -->
            <div id="booking-card" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Linked Booking</span>
                    <a id="booking-link" href="#" target="_blank" class="px-2.5 py-1 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 text-xs font-bold flex items-center space-x-1 transition">
                        <span id="booking-id-text">Booking #...</span>
                        <span>↗</span>
                    </a>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Service Title</span>
                    <div id="booking-title" class="text-sm font-bold text-white leading-snug">Loading...</div>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Category</span>
                    <div id="booking-category" class="text-xs font-semibold text-slate-300">General Inquiry</div>
                </div>
            </div>

            <!-- Initial Problem Statement -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block border-b border-slate-800 pb-2">Initial Inquiry</span>
                <p id="initial-message" class="text-xs text-slate-300 leading-relaxed whitespace-pre-line pt-1">
                    Loading description...
                </p>
                <div id="ticket-created-at" class="text-[10px] text-slate-500 font-mono pt-2 border-t border-slate-800/60">
                    --
                </div>
            </div>

            <!-- Reopen Ticket Control (if resolved) -->
            <div id="reopen-box" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hidden">
                <div class="text-xs text-slate-300 mb-2">Need more assistance with this ticket?</div>
                <button onclick="reopenTicket()" class="w-full px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-bold border border-slate-700 transition">
                    🔄 Reopen Ticket
                </button>
            </div>
        </div>

        <!-- Right / Main: Live Chat Window -->
        <div class="lg:col-span-2 rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden flex flex-col h-[700px] shadow-2xl">
            
            <!-- Chat Header -->
            <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
                    <div>
                        <div class="text-xs font-bold text-white flex items-center space-x-2">
                            <span>Platform Support Desk Live Chat</span>
                        </div>
                        <div class="text-[10px] text-slate-400">Direct real-time communication with platform helpdesk staff</div>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 font-mono" id="chat-ticket-id">
                    TKT-{{ substr($ticketId, 0, 8) }}
                </div>
            </div>

            <!-- Messages Stream Container -->
            <div id="chat-messages" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4 bg-slate-950/40">
                <div class="flex items-center justify-center py-12 text-slate-500 text-xs">
                    <svg class="animate-spin h-5 w-5 text-amber-400 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Connecting to live chat stream...</span>
                </div>
            </div>

            <!-- Canned Quick Replies -->
            <div class="px-4 py-2 bg-slate-900 border-t border-slate-800/80 flex items-center space-x-2 overflow-x-auto text-xs shrink-0">
                <span class="text-[10px] uppercase font-bold text-slate-500 whitespace-nowrap">Quick Replies:</span>
                <button type="button" onclick="useCannedReply('Awaiting payout confirmation for this booking.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    💰 Payout update
                </button>
                <button type="button" onclick="useCannedReply('Technician has reached the location but customer is unresponsive.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    📞 Customer unresponsive
                </button>
                <button type="button" onclick="useCannedReply('Please review the extra parts quotation submitted by technician.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    🔧 Quote review
                </button>
                <button type="button" onclick="useCannedReply('The issue is now fully resolved. Thank you!')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    ✅ Resolved
                </button>
            </div>

            <!-- Chat Input Composer -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 shrink-0">
                <form id="chat-form" onsubmit="sendProviderMessage(event)" class="flex items-center space-x-3">
                    <input type="text" id="chat-input" placeholder="Type your message to support... (Press Enter to send)"
                        class="flex-1 px-4 py-3 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition"
                        autocomplete="off">
                    <button type="submit" id="btn-send" class="px-5 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 hover:brightness-110 transition flex items-center space-x-1.5 shrink-0">
                        <span>Send</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Firebase SDKs -->
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.0/firebase-firestore-compat.js"></script>

<script>
    const ticketId = "{{ $ticketId }}";
    const currentProviderId = parseInt("{{ $provider->id }}");
    const currentProviderName = "{{ $provider->name }}";
    const currentCompanyName = "{{ $profile->company_name ?? $provider->name }}";

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

    let ticketData = null;
    let ticketUnsubscribe = null;
    let messagesUnsubscribe = null;

    // 1. Subscribe to Ticket Document
    function subscribeToTicket() {
        ticketUnsubscribe = db.collection('support_tickets').doc(ticketId)
            .onSnapshot((doc) => {
                if (!doc.exists) {
                    alert('Support ticket not found or closed.');
                    window.location.href = "{{ route('provider.support.index') }}";
                    return;
                }

                ticketData = doc.data();
                renderTicketDetails(ticketData);
            }, (err) => {
                console.error("Ticket snapshot error:", err);
            });
    }

    // 2. Render Ticket Meta
    function renderTicketDetails(data) {
        document.getElementById('header-ticket-number').innerText = data.ticket_number || ('TKT-' + ticketId.substring(0, 6).toUpperCase());
        document.getElementById('header-subject').innerText = data.subject || 'Support Request';

        const priority = (data.priority || 'medium').toUpperCase();
        const pBadge = document.getElementById('header-priority-badge');
        pBadge.innerText = priority;
        if (priority === 'HIGH') {
            pBadge.className = "px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-rose-500/10 text-rose-400 border border-rose-500/20";
        } else {
            pBadge.className = "px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20";
        }

        // Linked Booking
        if (data.booking_id) {
            document.getElementById('booking-id-text').innerText = 'Booking #' + data.booking_id;
            document.getElementById('booking-link').href = `{{ url('/provider/requests') }}/${data.booking_id}`;
            document.getElementById('booking-title').innerText = data.booking_title || 'Service Booking';
            document.getElementById('booking-category').innerText = data.category_name || 'General Inquiry';
            document.getElementById('booking-card').style.display = 'block';
        } else {
            document.getElementById('booking-card').style.display = 'none';
        }

        document.getElementById('initial-message').innerText = data.initial_message || data.last_message || '--';
        if (data.created_at) {
            const dt = data.created_at.toDate();
            document.getElementById('ticket-created-at').innerText = 'Submitted: ' + dt.toLocaleString();
        }

        // Status Pill
        const lower = (data.status || 'pending').toLowerCase();
        const pill = document.getElementById('live-status-pill');
        const reopenBox = document.getElementById('reopen-box');

        if (lower === 'done' || lower === 'resolved' || lower === 'closed') {
            pill.innerHTML = `<span class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Resolved ✓</span>`;
            reopenBox.classList.remove('hidden');
        } else if (lower === 'processing' || lower === 'in_progress') {
            pill.innerHTML = `<span class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">In Progress 💬</span>`;
            reopenBox.classList.add('hidden');
        } else {
            pill.innerHTML = `<span class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending ⏳</span>`;
            reopenBox.classList.add('hidden');
        }
    }

    // 3. Subscribe to Messages Subcollection
    function subscribeToMessages() {
        messagesUnsubscribe = db.collection('support_tickets').doc(ticketId)
            .collection('messages')
            .onSnapshot((snapshot) => {
                const messages = [];
                snapshot.forEach((doc) => {
                    const data = doc.data();
                    messages.push({
                        id: doc.id,
                        sender_id: String(data.sender_id),
                        sender_name: data.sender_name || 'User',
                        sender_role: (data.sender_role || 'provider').toLowerCase(),
                        message: data.message || '',
                        created_at: data.created_at ? data.created_at.toDate() : new Date(),
                        seen_at: data.seen_at
                    });

                    // Mark admin messages as seen by provider
                    if (data.sender_role === 'admin' && !data.seen_at) {
                        doc.ref.update({
                            seen_at: firebase.firestore.FieldValue.serverTimestamp()
                        }).catch(() => {});
                    }
                });

                // Clear unread count for provider
                db.collection('support_tickets').doc(ticketId).update({
                    unread_by_provider: 0
                }).catch(() => {});

                // Sort ascending by time
                messages.sort((a, b) => a.created_at - b.created_at);
                renderMessages(messages);
            }, (err) => {
                console.error("Messages stream error:", err);
            });
    }

    // 4. Render Messages
    function renderMessages(messages) {
        const container = document.getElementById('chat-messages');

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 text-slate-500 text-xs">
                    <span class="text-3xl mb-2">💬</span>
                    <span class="font-bold text-slate-300">Support Channel Open</span>
                    <span>No messages yet. Send a response below.</span>
                </div>
            `;
            return;
        }

        container.innerHTML = messages.map(m => {
            const timeStr = formatTime(m.created_at);

            // System Message
            if (m.sender_role === 'system') {
                return `
                    <div class="flex justify-center my-2">
                        <span class="px-3 py-1 rounded-full text-[10px] font-semibold bg-slate-900 border border-slate-800 text-slate-400">
                            ⚙️ ${escapeHtml(m.message)}
                        </span>
                    </div>
                `;
            }

            const isMe = m.sender_role === 'provider' || m.sender_id === String(currentProviderId);

            if (isMe) {
                // Provider's own message (right)
                return `
                    <div class="flex justify-end items-end space-x-2">
                        <div class="max-w-md bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 p-3.5 rounded-2xl rounded-br-none shadow-lg shadow-amber-500/10">
                            <div class="flex items-center justify-between space-x-4 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-900">${escapeHtml(m.sender_name)} (You)</span>
                                <span class="text-[10px] text-slate-900/70 font-mono">${timeStr}</span>
                            </div>
                            <div class="text-xs font-semibold leading-relaxed whitespace-pre-line">${escapeHtml(m.message)}</div>
                        </div>
                    </div>
                `;
            } else {
                // Admin / Support Team message (left)
                return `
                    <div class="flex justify-start items-end space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-800 text-amber-400 font-bold flex items-center justify-center text-xs shrink-0 border border-slate-700">
                            S
                        </div>
                        <div class="max-w-md bg-slate-800 text-slate-200 p-3.5 rounded-2xl rounded-bl-none border border-slate-700/80 shadow-md">
                            <div class="flex items-center justify-between space-x-4 mb-1">
                                <span class="text-[10px] font-bold text-amber-400">Platform Support Team</span>
                                <span class="text-[10px] text-slate-400 font-mono">${timeStr}</span>
                            </div>
                            <div class="text-xs leading-relaxed whitespace-pre-line">${escapeHtml(m.message)}</div>
                        </div>
                    </div>
                `;
            }
        }).join('');

        // Auto Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    // 5. Send Provider Message
    async function sendProviderMessage(e) {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;

        const btnSend = document.getElementById('btn-send');
        btnSend.disabled = true;
        btnSend.classList.add('opacity-50');

        try {
            // Write to Messages subcollection
            await db.collection('support_tickets').doc(ticketId).collection('messages').add({
                sender_id: String(currentProviderId),
                sender_name: currentCompanyName || currentProviderName,
                sender_role: 'provider',
                message: text,
                attachments: [],
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                seen_at: null
            });

            // Update parent Ticket document
            const updatePayload = {
                last_message: text,
                last_message_by: 'provider',
                updated_at: firebase.firestore.FieldValue.serverTimestamp(),
                unread_by_admin: firebase.firestore.FieldValue.increment(1)
            };

            // If ticket was done, advance it back to processing
            if (ticketData && (ticketData.status === 'done' || ticketData.status === 'resolved')) {
                updatePayload.status = 'processing';
            }

            await db.collection('support_tickets').doc(ticketId).update(updatePayload);

            input.value = '';
            input.focus();
        } catch (err) {
            console.error("Error sending message:", err);
            alert("Failed to send message: " + err.message);
        } finally {
            btnSend.disabled = false;
            btnSend.classList.remove('opacity-50');
        }
    }

    async function reopenTicket() {
        try {
            await db.collection('support_tickets').doc(ticketId).update({
                status: 'processing',
                updated_at: firebase.firestore.FieldValue.serverTimestamp()
            });

            await db.collection('support_tickets').doc(ticketId).collection('messages').add({
                sender_id: 'system',
                sender_name: 'System',
                sender_role: 'system',
                message: `🔄 Provider requested further assistance. Ticket reopened to Processing.`,
                attachments: [],
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                seen_at: null
            });
        } catch (e) {
            console.error("Failed to reopen ticket:", e);
        }
    }

    function useCannedReply(text) {
        const input = document.getElementById('chat-input');
        input.value = text;
        input.focus();
    }

    function formatTime(d) {
        if (!d) return '';
        const date = new Date(d);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    document.addEventListener('DOMContentLoaded', () => {
        subscribeToTicket();
        subscribeToMessages();
    });
</script>
@endsection
