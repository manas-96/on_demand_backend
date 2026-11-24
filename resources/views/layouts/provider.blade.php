<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Provider Portal') - {{ config('app.name', 'Grab A Hand') }}</title>
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
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
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
            <span class="font-bold text-white tracking-tight">Grab A Hand Provider Portal</span>
        </div>
        <button onclick="document.getElementById('sidebar-panel').classList.toggle('hidden')" class="p-2 text-slate-400 hover:text-white rounded-lg bg-slate-800 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <!-- Left Navigation Sidebar Panel for Provider -->
    <aside id="sidebar-panel" class="w-full md:w-72 bg-slate-900/90 border-r border-slate-800/80 flex flex-col justify-between hidden md:flex shrink-0 md:fixed md:top-0 md:left-0 md:h-screen z-40 glass-panel">
        <div class="flex flex-col h-full min-h-0">
            <!-- Brand Logo & Header -->
            <div class="h-20 px-6 flex items-center justify-between border-b border-slate-800/60 shrink-0">
                <a href="{{ route('provider.dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 p-0.5 shadow-lg shadow-amber-500/25">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div>
                        <span class="font-extrabold text-base text-white tracking-tight block leading-none">Grab A Hand</span>
                        <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-widest mt-1 block">Provider Console</span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links (Fixed, Non-Scrollable) -->
            <div class="flex-1 overflow-hidden px-4 py-5 space-y-5">
                <div class="space-y-1">
                    <div class="px-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Company Management</div>
                    
                    <a href="{{ route('provider.dashboard') }}" 
                        class="flex items-center space-x-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.dashboard') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('provider.dashboard') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('provider.technicians.index') }}" 
                        class="flex items-center space-x-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.technicians.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('provider.technicians.*') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Technicians</span>
                    </a>

                    <a href="{{ route('provider.requests.incoming') }}" 
                        class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.requests.incoming') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 {{ request()->routeIs('provider.requests.incoming') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <span>Incoming Requests</span>
                        </div>
                        @php
                            $pendingCount = \App\Models\ProviderJobRequest::where('provider_id', auth()->id())
                                ->whereIn('status', ['sent', 'viewed'])
                                ->whereDoesntHave('serviceRequest.jobAssignments', function ($q) {
                                    $q->where('provider_id', auth()->id());
                                })->count();
                        @endphp
                        @if($pendingCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ request()->routeIs('provider.requests.incoming') ? 'bg-slate-950 text-amber-400' : 'bg-amber-400/20 text-amber-400 border border-amber-400/30' }}">
                                {{ $pendingCount }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('provider.requests.accepted') }}" 
                        class="flex items-center space-x-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.requests.accepted') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('provider.requests.accepted') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Accepted Jobs</span>
                    </a>

                    <a href="{{ route('provider.earnings.index') }}" 
                        class="flex items-center space-x-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.earnings.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('provider.earnings.*') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Earnings</span>
                    </a>

                    <a href="{{ route('provider.support.index') }}" 
                        class="flex items-center space-x-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition-all duration-150 {{ request()->routeIs('provider.support.*') ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('provider.support.*') ? 'text-slate-950' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Help & Support</span>
                    </a>
                </div>
            </div>

            <!-- Profile Info Footer -->
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

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen md:ml-72">
        <header class="h-20 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl px-6 md:px-8 flex items-center justify-between sticky top-0 z-30">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">@yield('title', 'Company Dashboard')</h1>
                <p class="text-xs text-slate-400 hidden sm:block">Service Provider Organization Console</p>
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
                        <a href="{{ route('provider.profile.show', ['tab' => 'profile']) }}" 
                           class="flex items-center space-x-3 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/80 transition">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>My Profile</span>
                        </a>

                        <!-- 2. Change Password -->
                        <a href="{{ route('provider.profile.show', ['tab' => 'password']) }}" 
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
    </div>

    <script>
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
