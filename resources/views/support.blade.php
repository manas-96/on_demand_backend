<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support & Help Center - Grab A Hand Service Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 antialiased min-h-screen flex flex-col justify-between">

    <!-- Top Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-3 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 p-0.5 shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <div>
                    <span class="font-extrabold text-base text-white tracking-tight block leading-none">Grab A Hand</span>
                    <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-widest block mt-0.5">Support & Help Center</span>
                </div>
            </a>

            <div class="flex items-center space-x-4 text-xs font-semibold">
                <a href="{{ route('privacy-policy') }}" class="text-slate-400 hover:text-white transition hidden sm:inline-block">Privacy Policy</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 transition">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold transition">Sign In</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-12 flex-1 w-full space-y-12">

        <!-- Hero Header -->
        <div class="text-center space-y-4 max-w-3xl mx-auto">
            <span class="px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-xs uppercase tracking-widest inline-block">
                Customer Support 💬
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">How Can We Help You Today?</h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                Welcome to the Grab A Hand Support Portal. Have a question about a booking, service execution, invoice, or account? Get in touch with our team or browse our FAQs below.
            </p>
        </div>

        @if (session('success'))
            <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-start space-x-3 max-w-4xl mx-auto">
                <svg class="w-6 h-6 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <strong class="block font-bold">Submission Received!</strong>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1 max-w-4xl mx-auto">
                <strong class="block font-bold">Please correct the following errors:</strong>
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <!-- Quick Contact Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2 text-center sm:text-left">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 mb-3 mx-auto sm:mx-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Email Support</h3>
                <p class="text-xs text-slate-400">Reach our dedicated support team via email for fast response times.</p>
                <a href="mailto:support@grabahand.com" class="text-xs font-semibold text-amber-400 hover:underline block pt-1">support@grabahand.com →</a>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2 text-center sm:text-left">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-3 mx-auto sm:mx-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Toll-Free Helpline</h3>
                <p class="text-xs text-slate-400">Speak directly with a support representative for active service issues.</p>
                <a href="tel:18005556636" class="text-xs font-semibold text-emerald-400 hover:underline block pt-1">1-800-555-GRAB-A-HAND</a>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2 text-center sm:text-left">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-3 mx-auto sm:mx-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Operating Hours</h3>
                <p class="text-xs text-slate-400">Our customer support desks operate 7 days a week.</p>
                <span class="text-xs font-semibold text-slate-300 block pt-1">Mon–Sun: 8:00 AM – 10:00 PM EST</span>
            </div>
        </div>

        <!-- Open Support Request Form & FAQs Split Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-5xl mx-auto">
            
            <!-- Left: Open Support Contact Form -->
            <div class="lg:col-span-7 p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-6 shadow-2xl">
                <div class="border-b border-slate-800 pb-4">
                    <h2 class="text-xl font-bold text-white tracking-tight">Submit Support Inquiry</h2>
                    <p class="text-xs text-slate-400 mt-1">Fill out the form below. Anyone can submit a support message without log in requirement.</p>
                </div>

                <form method="POST" action="{{ route('support.store') }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Your Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required placeholder="e.g. Alex Morgan"
                                   class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition placeholder-slate-600">
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Email Address *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required placeholder="e.g. alex@example.com"
                                   class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition placeholder-slate-600">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Phone Number (Optional)</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="e.g. +1 555 123 4567"
                                   class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition placeholder-slate-600">
                        </div>

                        <div>
                            <label for="category" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Inquiry Category *</label>
                            <select id="category" name="category" required class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                                <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                                <option value="booking" {{ old('category') === 'booking' ? 'selected' : '' }}>Service Booking & Schedule</option>
                                <option value="payment" {{ old('category') === 'payment' ? 'selected' : '' }}>Payments & Invoice Billing</option>
                                <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>User Account & Credentials</option>
                                <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical Issue / Bug</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Subject Summary *</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="Brief title of your inquiry..."
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition placeholder-slate-600">
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Detailed Message *</label>
                        <textarea id="message" name="message" rows="5" required placeholder="Please describe your question or issue in detail..."
                                  class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition placeholder-slate-600">{{ old('message') }}</textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-3 px-6 bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-extrabold text-sm rounded-xl shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                            Send Support Ticket 🚀
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Frequently Asked Questions -->
            <div class="lg:col-span-5 space-y-6">
                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <h2 class="text-lg font-bold text-white tracking-tight border-b border-slate-800 pb-3">Frequently Asked Questions</h2>

                    <div class="space-y-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white text-sm block">How does service booking work?</strong>
                            <p class="text-slate-400 leading-relaxed">Submit your problem details and photos. Verified service providers review and submit quotes or assign certified technicians to your project.</p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white text-sm block">How are invoices generated?</strong>
                            <p class="text-slate-400 leading-relaxed">Once technician completes site inspection or job execution, an itemized server invoice is generated including subtotal, tax, and optional tip.</p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white text-sm block">Can I track technician dispatch?</strong>
                            <p class="text-slate-400 leading-relaxed">Yes! Technicians update live duty status and site progress checkpoints (`arrived`, `in_progress`, `completed`) in real-time.</p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <strong class="text-white text-sm block">Where can I view Privacy Policy?</strong>
                            <p class="text-slate-400 leading-relaxed">You can read our full legal <a href="{{ route('privacy-policy') }}" class="text-amber-400 underline">Privacy Policy Document here</a>.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-900/60 py-6 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>© {{ date('Y') }} Grab A Hand Service Marketplace Inc. All rights reserved.</div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('support') }}" class="text-amber-400 hover:underline">Support Center</a>
                <span>•</span>
                <a href="{{ route('privacy-policy') }}" class="text-slate-400 hover:underline">Privacy Policy</a>
            </div>
        </div>
    </footer>

</body>
</html>
