@extends('layouts.admin')

@section('title', 'Support Chat - ' . $ticketId)

@section('content')
<div class="space-y-6">
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.support.index') }}" class="text-xs text-amber-400 hover:underline mb-1 inline-flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Customer Support Registry</span>
            </a>
            <div class="flex items-center space-x-3 mt-1">
                <h1 id="header-ticket-number" class="text-2xl font-mono font-bold text-white">Ticket Loading...</h1>
                <span id="header-priority-badge" class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-slate-800 text-slate-300">MEDIUM</span>
            </div>
            <p id="header-subject" class="text-xs text-slate-400 mt-0.5">Live dedicated communication channel</p>
        </div>

        <!-- Live Status Controls -->
        <div class="flex items-center space-x-2 bg-slate-900 border border-slate-800 p-1.5 rounded-2xl">
            <span class="text-xs text-slate-400 font-semibold px-2">Status:</span>
            <button onclick="updateTicketStatus('pending')" id="btn-status-pending" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white">
                <span class="w-2 h-2 rounded-full bg-amber-400 mr-1"></span>
                <span>Pending</span>
            </button>
            <button onclick="updateTicketStatus('processing')" id="btn-status-processing" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white">
                <span class="w-2 h-2 rounded-full bg-blue-400 mr-1"></span>
                <span>Processing</span>
            </button>
            <button onclick="updateTicketStatus('done')" id="btn-status-done" class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white">
                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-1"></span>
                <span>Done</span>
            </button>
        </div>
    </div>

    <!-- Main Chat & Context Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Left Sidebar: Ticket & Customer Info -->
        <div class="space-y-5">
            <!-- Linked Booking Info Card -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
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
                    <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Service Category</span>
                    <div id="booking-category" class="text-xs font-semibold text-slate-300">General Service</div>
                </div>
            </div>

            <!-- Requester Profile Card -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                <span id="party-card-title" class="text-xs font-bold uppercase tracking-wider text-slate-400 block border-b border-slate-800 pb-3">Requester Information</span>
                <div class="flex items-center space-x-3 pt-1">
                    <div id="customer-avatar" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-amber-400 text-base">
                        C
                    </div>
                    <div>
                        <div id="customer-name" class="text-sm font-bold text-white">Loading Customer...</div>
                        <div id="customer-phone" class="text-xs text-slate-400 mt-0.5">--</div>
                    </div>
                </div>
                <div class="pt-2 text-xs text-slate-300 flex items-center space-x-2">
                    <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <a id="customer-email" href="#" class="text-amber-400 hover:underline truncate">--</a>
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
        </div>

        <!-- Right / Main: Live Chat Window -->
        <div class="lg:col-span-2 rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden flex flex-col h-[700px] shadow-2xl">
            
            <!-- Chat Header -->
            <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
                    <div>
                        <div class="text-xs font-bold text-white flex items-center space-x-2">
                            <span>Live Customer Support Channel</span>
                        </div>
                        <div class="text-[10px] text-slate-400">Direct real-time communication with customer</div>
                    </div>
                </div>
                <div id="live-status-pill">
                    <!-- Status Pill Rendered via JS -->
                </div>
            </div>

            <!-- Messages Stream Container -->
            <div id="chat-messages" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4 bg-slate-950/40">
                <div class="flex items-center justify-center py-12 text-slate-500 text-xs">
                    <svg class="animate-spin h-5 w-5 text-amber-400 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <span>Loading conversation history...</span>
                </div>
            </div>

            <!-- Canned Quick Replies -->
            <div class="px-4 py-2 bg-slate-900 border-t border-slate-800/80 flex items-center space-x-2 overflow-x-auto text-xs shrink-0">
                <span class="text-[10px] uppercase font-bold text-slate-500 whitespace-nowrap">Quick Replies:</span>
                <button type="button" onclick="useCannedReply('Hello! I am reviewing your booking details right now.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    👋 Reviewing details
                </button>
                <button type="button" onclick="useCannedReply('We have contacted your service provider regarding this inquiry.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    🔧 Provider contacted
                </button>
                <button type="button" onclick="useCannedReply('A payment adjustment/refund has been initiated for your booking.')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    💳 Payment initiated
                </button>
                <button type="button" onclick="useCannedReply('Your issue has been resolved. Please let us know if you need any additional assistance!')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] whitespace-nowrap transition border border-slate-700">
                    ✅ Issue resolved
                </button>
            </div>

            <!-- Chat Input Composer -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 shrink-0">
                <form id="chat-form" onsubmit="sendAdminMessage(event)" class="flex items-center space-x-3">
                    <input type="text" id="chat-input" placeholder="Type your response to the customer... (Press Enter to send)"
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
    const adminName = "{{ $adminUser->name ?? 'Support Team' }}";

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
                    alert('Support ticket not found or deleted.');
                    window.location.href = "{{ route('admin.support.index') }}";
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
            document.getElementById('booking-link').href = `{{ url('/admin/requests') }}/${data.booking_id}`;
        } else {
            document.getElementById('booking-id-text').innerText = 'No Booking Linked';
            document.getElementById('booking-link').style.display = 'none';
        }
        document.getElementById('booking-title').innerText = data.booking_title || 'General Support Booking';
        document.getElementById('booking-category').innerText = data.category_name || 'General Inquiry';

        // Requester (Customer or Provider)
        const userRole = (data.user_role || (data.provider_id ? 'provider' : 'customer')).toLowerCase();
        const isProvider = userRole === 'provider';
        if (document.getElementById('party-card-title')) {
            document.getElementById('party-card-title').innerText = isProvider ? '🏢 Provider Information' : '👤 Customer Information';
        }

        const partyName = isProvider ? (data.company_name || data.provider_name || 'Provider') : (data.customer_name || 'Customer');
        const partyPhone = isProvider ? (data.provider_phone || 'No phone') : (data.customer_phone || 'No phone provided');
        const partyEmail = isProvider ? (data.provider_email || 'No email') : (data.customer_email || 'No email provided');

        document.getElementById('customer-name').innerText = partyName;
        document.getElementById('customer-phone').innerText = partyPhone;
        document.getElementById('customer-email').innerText = partyEmail;
        document.getElementById('customer-email').href = partyEmail.includes('@') ? `mailto:${partyEmail}` : '#';
        document.getElementById('customer-avatar').innerText = (partyName || 'U')[0].toUpperCase();

        document.getElementById('initial-message').innerText = data.initial_message || data.last_message || '--';
        if (data.created_at) {
            const dt = data.created_at.toDate();
            document.getElementById('ticket-created-at').innerText = 'Submitted: ' + dt.toLocaleString();
        }

        // Update Status Buttons State
        updateStatusButtons(data.status || 'pending');
    }

    function updateStatusButtons(status) {
        const lower = (status || 'pending').toLowerCase();
        
        const btnPending = document.getElementById('btn-status-pending');
        const btnProcessing = document.getElementById('btn-status-processing');
        const btnDone = document.getElementById('btn-status-done');
        const pill = document.getElementById('live-status-pill');

        // Reset all
        btnPending.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white hover:bg-slate-800";
        btnProcessing.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white hover:bg-slate-800";
        btnDone.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 text-slate-400 hover:text-white hover:bg-slate-800";

        if (lower === 'done' || lower === 'resolved' || lower === 'closed') {
            btnDone.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
            pill.innerHTML = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Resolved ✓</span>`;
        } else if (lower === 'processing' || lower === 'in_progress') {
            btnProcessing.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 bg-blue-500/20 text-blue-400 border border-blue-500/30";
            pill.innerHTML = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20">In Progress 💬</span>`;
        } else {
            btnPending.className = "px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1 bg-amber-500/20 text-amber-400 border border-amber-500/30";
            pill.innerHTML = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20">Pending ⏳</span>`;
        }
    }

    // 3. Update Status in Firestore
    async function updateTicketStatus(newStatus) {
        try {
            await db.collection('support_tickets').doc(ticketId).update({
                status: newStatus,
                updated_at: firebase.firestore.FieldValue.serverTimestamp()
            });

            // Post a system note in the chat
            const statusLabel = newStatus === 'done' ? 'Resolved' : (newStatus === 'processing' ? 'Processing' : 'Pending');
            await db.collection('support_tickets').doc(ticketId).collection('messages').add({
                sender_id: 'system',
                sender_name: 'System',
                sender_role: 'system',
                message: `Status updated to ${statusLabel} by ${adminName}.`,
                attachments: [],
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                seen_at: null
            });
        } catch (e) {
            console.error("Failed to update status:", e);
            alert("Error updating status: " + e.message);
        }
    }

    // 4. Subscribe to Messages Subcollection
    function subscribeToMessages() {
        messagesUnsubscribe = db.collection('support_tickets').doc(ticketId)
            .collection('messages')
            .onSnapshot((snapshot) => {
                const messages = [];
                snapshot.forEach((doc) => {
                    const data = doc.data();
                    messages.push({
                        id: doc.id,
                        sender_id: data.sender_id,
                        sender_name: data.sender_name || 'User',
                        sender_role: (data.sender_role || 'customer').toLowerCase(),
                        message: data.message || '',
                        created_at: data.created_at ? data.created_at.toDate() : new Date(),
                        seen_at: data.seen_at
                    });

                    // Mark customer message as seen by admin
                    if (data.sender_role === 'customer' && !data.seen_at) {
                        doc.ref.update({
                            seen_at: firebase.firestore.FieldValue.serverTimestamp()
                        }).catch(() => {});
                    }
                });

                // Clear unread count for admin
                db.collection('support_tickets').doc(ticketId).update({
                    unread_by_admin: 0
                }).catch(() => {});

                // Sort ascending by time
                messages.sort((a, b) => a.created_at - b.created_at);
                renderMessages(messages);
            }, (err) => {
                console.error("Messages stream error:", err);
            });
    }

    // 5. Render Messages
    function renderMessages(messages) {
        const container = document.getElementById('chat-messages');

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 text-slate-500 text-xs">
                    <span class="text-3xl mb-2">💬</span>
                    <span class="font-bold text-slate-300">Conversation started</span>
                    <span>No messages exchanged yet. Send a response below.</span>
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

            const isAdmin = m.sender_role === 'admin';

            if (isAdmin) {
                return `
                    <div class="flex justify-end items-end space-x-2">
                        <div class="max-w-md bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-950 p-3.5 rounded-2xl rounded-br-none shadow-lg shadow-amber-500/10">
                            <div class="flex items-center justify-between space-x-4 mb-1">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-900">${escapeHtml(m.sender_name)} (Support Team)</span>
                                <span class="text-[10px] text-slate-900/70 font-mono">${timeStr}</span>
                            </div>
                            <div class="text-xs font-semibold leading-relaxed whitespace-pre-line">${escapeHtml(m.message)}</div>
                        </div>
                    </div>
                `;
            } else {
                const roleLabel = (m.sender_role === 'provider' || ticketData?.user_role === 'provider') ? 'Provider' : 'Customer';
                return `
                    <div class="flex justify-start items-end space-x-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-800 text-amber-400 font-bold flex items-center justify-center text-xs shrink-0 border border-slate-700">
                            ${(m.sender_name || 'U')[0].toUpperCase()}
                        </div>
                        <div class="max-w-md bg-slate-800 text-slate-200 p-3.5 rounded-2xl rounded-bl-none border border-slate-700/80 shadow-md">
                            <div class="flex items-center justify-between space-x-4 mb-1">
                                <span class="text-[10px] font-bold text-amber-400">${escapeHtml(m.sender_name)} (${roleLabel})</span>
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

    // 6. Send Admin Message
    async function sendAdminMessage(e) {
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
                sender_id: 'admin',
                sender_name: adminName,
                sender_role: 'admin',
                message: text,
                attachments: [],
                created_at: firebase.firestore.FieldValue.serverTimestamp(),
                seen_at: null
            });

            // Update parent Ticket document
            const isProviderTicket = ticketData?.user_role === 'provider';
            const updatePayload = {
                last_message: text,
                last_message_by: 'admin',
                updated_at: firebase.firestore.FieldValue.serverTimestamp(),
                unread_by_customer: isProviderTicket ? 0 : firebase.firestore.FieldValue.increment(1),
                unread_by_provider: isProviderTicket ? firebase.firestore.FieldValue.increment(1) : 0
            };

            // If ticket was pending, advance it to processing
            if (ticketData && ticketData.status === 'pending') {
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
