<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Portal') - {{ config('app.name', 'Grab A Hand') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        yellow: {
                            300: '#ffe07a',
                            400: '#ffd447',
                            500: '#f6b100',
                            600: '#d99b00',
                            700: '#b27f00',
                        },
                        amber: {
                            300: '#ffe07a',
                            400: '#ffd447',
                            500: '#f6b100',
                            600: '#d99b00',
                            700: '#b27f00',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(245, 158, 11, 0.5) rgba(15, 23, 42, 0.8);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(245, 158, 11, 0.5);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(245, 158, 11, 0.8);
        }
    </style>
</head>
<body class="h-full text-slate-200 antialiased flex flex-col md:flex-row overflow-x-hidden">

    <!-- Mobile Header -->
    <div class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900 border-b border-slate-800 sticky top-0 z-50">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 flex items-center justify-center text-slate-950 font-extrabold shadow-lg shadow-amber-500/30">
                G
            </div>
            <span class="font-bold text-white tracking-tight">Grab A Hand</span>
        </div>
        <button onclick="document.getElementById('sidebar-panel').classList.toggle('hidden')" class="p-2 text-slate-400 hover:text-white rounded-lg bg-slate-800 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <!-- Left Navigation Sidebar Panel -->
    <aside id="sidebar-panel" class="w-full md:w-72 bg-slate-900/90 border-r border-slate-800/80 flex flex-col justify-between hidden md:flex shrink-0 md:fixed md:top-0 md:left-0 md:h-screen z-40 glass-panel">
        
        <div class="flex flex-col h-full min-h-0">
            
            <!-- Brand Logo & Header -->
            <div class="h-20 px-6 flex items-center justify-between border-b border-slate-800/60 shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 via-amber-500 to-yellow-500 p-0.5 shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform duration-200">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </div>
                    <div>
                        <span class="font-extrabold text-base text-white tracking-tight block leading-none">Grab A Hand</span>
                        <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-widest mt-1 block">Admin Web Panel</span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links List (Scrollable) -->
            <div class="flex-1 overflow-y-auto custom-scrollbar px-4 py-5 space-y-5">
                
                <!-- Main Operations Section -->
                <div class="space-y-1">
                    <div class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Main Controls</div>
                    
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Dashboard</span>
                        </div>
                    </a>

                    <!-- Request Inspector -->
                    <a href="{{ route('admin.requests.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.requests.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.requests.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            <span>Service Requests</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-amber-400 border border-amber-500/20">
                            {{ $sidebarCounts['requests'] ?? 0 }}
                        </span>
                    </a>
                </div>

                <!-- User & Entity Management Section -->
                <div class="space-y-1">
                    <div class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Actor Directory</div>

                    <!-- Customers -->
                    <a href="{{ route('admin.customers.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.customers.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.customers.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Customers</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-slate-400">
                            {{ $sidebarCounts['customers'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Providers -->
                    <a href="{{ route('admin.providers.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.providers.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.providers.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Service Providers</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ $sidebarCounts['providers'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Technicians -->
                    <a href="{{ route('admin.technicians.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.technicians.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.technicians.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2V4zm-6 8a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2v-1zm12 0a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2v-1z"/></svg>
                            <span>Field Technicians</span>
                        </div>
                    </a>

                    <!-- Service Categories -->
                    <a href="{{ route('admin.services.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.services.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.services.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            <span>Service Categories</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-amber-400 border border-amber-400/20">
                            {{ $sidebarCounts['categories'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Promotional Banners -->
                    <a href="{{ route('admin.banners.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.banners.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.banners.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Banners</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-amber-400 border border-amber-400/20">
                            {{ $sidebarCounts['banners'] ?? 0 }}
                        </span>
                    </a>

                    <!-- Promotional Videos -->
                    <a href="{{ route('admin.videos.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.videos.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.videos.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span>Promotional Videos</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-amber-400 border border-amber-400/20">
                            {{ $sidebarCounts['videos'] ?? 0 }}
                        </span>
                    </a>
                </div>

                <!-- Financials & Quality Section -->
                <div class="space-y-1">
                    <div class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Finance & Quality</div>

                    <!-- Invoices -->
                    <a href="{{ route('admin.financials.invoices') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.financials.invoices') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.financials.invoices') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            <span>Invoices & Ledger</span>
                        </div>
                    </a>

                    <!-- Provider Earnings -->
                    <a href="{{ route('admin.financials.earnings') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.financials.earnings') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.financials.earnings') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Provider Earnings</span>
                        </div>
                    </a>

                    <!-- Ratings & Reviews -->
                    <a href="{{ route('admin.reviews.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.reviews.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.reviews.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            <span>Customer Reviews</span>
                        </div>
                    </a>

                    <!-- Push Notifications -->
                    <a href="{{ route('admin.notifications.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.notifications.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.notifications.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span>Push Notifications</span>
                        </div>
                    </a>

                    <!-- Email Diagnostics -->
                    <a href="{{ route('admin.email-test.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.email-test.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.email-test.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Email Diagnostics</span>
                        </div>
                    </a>

                    <!-- Settings & Audit -->
                    <a href="{{ route('admin.settings.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Settings & Audit</span>
                        </div>
                    </a>

                    <!-- Privacy Policy -->
                    <a href="{{ route('admin.privacy-policy.edit') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.privacy-policy.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.privacy-policy.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span>Privacy Policy</span>
                        </div>
                    </a>

                    <!-- Customer Support -->
                    <a href="{{ route('admin.support.index') }}" 
                        class="group flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('admin.support.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('admin.support.*') ? 'text-slate-950' : 'text-slate-400 group-hover:text-amber-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Customer Support</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/10 text-amber-400 border border-amber-400/20">
                            Live
                        </span>
                    </a>
                </div>

            </div>

            <!-- Profile Info & Logout Footer Card -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/60 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-white shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="truncate">
                            <div class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign Out" class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    <!-- Main Content Area Container -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen md:ml-72">
        
        <!-- Top Header Bar -->
        <header class="h-20 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl px-6 md:px-8 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">@yield('title', 'Dashboard')</h1>
                <p class="text-xs text-slate-400 hidden sm:block">Grab A Hand Service Marketplace Management</p>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Profile Dropdown Component -->
                <div class="relative" id="profile-dropdown-container">
                    <button onclick="document.getElementById('profile-dropdown-menu').classList.toggle('hidden')" 
                            class="flex items-center space-x-2.5 p-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition focus:outline-none">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-amber-400 to-yellow-500 flex items-center justify-center text-slate-950 font-bold text-xs shadow">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="text-xs font-bold text-white hidden sm:block">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <!-- Dropdown Menu Options -->
                    <div id="profile-dropdown-menu" class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl py-2 z-50 backdrop-blur-xl">
                        <div class="px-4 py-2 border-b border-slate-800 mb-1">
                            <div class="text-xs font-bold text-white truncate">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                        </div>

                        <!-- 1. My Profile -->
                        <a href="{{ route('admin.profile.show', ['tab' => 'profile']) }}" 
                           class="flex items-center space-x-3 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>My Profile</span>
                        </a>

                        <!-- 2. Change Password -->
                        <a href="{{ route('admin.profile.show', ['tab' => 'password']) }}" 
                           class="flex items-center space-x-3 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Change Password</span>
                        </a>

                        <div class="border-t border-slate-800 my-1"></div>

                        <!-- 3. Logout -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center space-x-3 px-4 py-2.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition text-left">
                                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dynamic Body Page Content -->
        <main class="p-6 md:p-8 flex-1 space-y-6 max-w-7xl w-full mx-auto">

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-emerald-950/20">
                    <span>✓ {{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-medium flex items-center justify-between shadow-lg shadow-rose-950/20">
                    <span>⚠️ {{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/60 bg-slate-950/80 py-4 px-8 text-center text-xs text-slate-500">
            Grab A Hand Admin Control Panel &copy; {{ date('Y') }}
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "M j, Y",
                allowInput: true,
                theme: "dark"
            });
        });

        document.addEventListener('click', function(event) {
            const container = document.getElementById('profile-dropdown-container');
            const menu = document.getElementById('profile-dropdown-menu');
            if (container && menu && !container.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
