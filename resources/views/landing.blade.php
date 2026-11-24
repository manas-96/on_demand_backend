<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grab A Hand - Backend & Web Portals</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between antialiased">

    <!-- Header Navigation -->
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 flex items-center justify-center font-bold text-slate-950 shadow-lg shadow-amber-500/30">
                    GH
                </div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                    Grab A Hand
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('register.provider') }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition">
                    Become a Provider
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 flex flex-col justify-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Text -->
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-400/10 text-amber-400 border border-amber-400/20 text-xs font-medium">
                    <span>🚀 Enterprise Multi-Actor Platform</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-none">
                    Grab A Hand Service <br/>
                    <span class="bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 bg-clip-text text-transparent">
                        Marketplace Engine
                    </span>
                </h1>
                <p class="text-lg text-slate-400 max-w-2xl leading-relaxed">
                    Powering Admin Web Controls, Service Provider Portals, and Customer & Technician Mobile Apps with strict workflow state machines, direct provider acceptance, and server-validated pricing.
                </p>

                <!-- Feature Cards -->
                <div class="space-y-4">
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
                        <div class="text-2xl font-bold text-amber-400">15-Stage Workflow</div>
                        <div class="text-xs text-slate-400">End-to-end status lifecycle</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
                        <div class="text-2xl font-bold text-indigo-400">Haversine GPS</div>
                        <div class="text-xs text-slate-400">Instant provider acceptance</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                        <div class="text-amber-400 font-semibold mb-1">🛡️ Protected</div>
                        <div class="text-xs text-slate-400">Atomic bid selection</div>
                    </div>
                </div>

                <!-- App Store & Google Play Download Buttons -->
                <div class="pt-6 border-t border-slate-800 space-y-3">
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-400">Download Mobile Apps (Customer & Technician)</div>
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Android App Download Button -->
                        <a href="#download-android" onclick="alert('Starting Grab A Hand Android App (.apk) download...'); return false;" 
                           class="flex items-center space-x-3 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-white shadow-lg shadow-black/20 hover:border-amber-400/40 transition group">
                            <svg class="w-6 h-6 text-emerald-400 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.523 15.3414c-.5511 0-.9993-.4482-.9993-.9993s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9993-.9993.9993zm-11.046 0c-.5511 0-.9993-.4482-.9993-.9993s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9993-.9993.9993zm11.4045-6.0207l1.9973-3.4592c.1256-.2175.051-.4956-.1665-.6212-.2175-.1256-.4956-.051-.6212.1665l-2.0232 3.5041C15.4283 8.3585 13.7667 8 12 8s-3.4283.3585-5.0689.9109L4.9079 5.4068c-.1256-.2175-.4037-.2921-.6212-.1665-.2175.1256-.2921.4037-.1665.6212l1.9973 3.4592C2.6847 11.0731.3359 13.9712 0 17.5h24c-.3359-3.5288-2.6847-6.4269-6.1185-8.1793z"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-[10px] uppercase tracking-wider text-slate-400 font-medium leading-none">Get it on</div>
                                <div class="text-sm font-bold text-white leading-tight mt-0.5">Google Play (Android)</div>
                            </div>
                        </a>

                        <!-- iOS App Download Button -->
                        <a href="#download-ios" onclick="alert('Redirecting to Apple App Store for Grab A Hand iOS App...'); return false;" 
                           class="flex items-center space-x-3 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-750 border border-slate-700 text-white shadow-lg shadow-black/20 hover:border-amber-400/40 transition group">
                            <svg class="w-6 h-6 text-slate-200 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.32c.67-.82 1.13-1.97.99-3.12-1 .04-2.19.67-2.88 1.47-.61.71-1.14 1.87-.99 3 1.11.09 2.23-.53 2.88-1.35z"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-[10px] uppercase tracking-wider text-slate-400 font-medium leading-none">Download on the</div>
                                <div class="text-sm font-bold text-white leading-tight mt-0.5">App Store (iOS)</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Login Form Card -->
            <div class="lg:col-span-5">
                <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-8 shadow-2xl shadow-amber-950/20 relative">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-white">Sign In to Portal</h2>
                        <p class="text-sm text-slate-400 mt-1">Access your Admin or Service Provider Web Panel</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email', 'admin@example.com') }}" required 
                                class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                            <input type="password" id="password" name="password" value="password" required 
                                class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition">
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-amber-400">
                                <span>Remember me</span>
                            </label>
                        </div>

                        <button type="submit" 
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all duration-200">
                            Sign In
                        </button>
                    </form>

                    <div class="mt-4 pt-4 border-t border-slate-700/60 text-center text-xs text-slate-400">
                        Are you a service business? 
                        <a href="{{ route('register.provider') }}" class="text-amber-400 font-bold hover:underline">Register as Provider &rarr;</a>
                    </div>

                    <!-- Quick Demo Credentials Selector -->
                    <div class="mt-8 pt-6 border-t border-slate-700/60">
                        <span class="text-xs uppercase font-semibold tracking-wider text-slate-500 block mb-3">Quick Demo Credentials</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button onclick="fillAuth('admin@example.com')" class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-700 text-left transition">
                                <strong class="block text-white">Admin User</strong>
                                admin@example.com
                            </button>
                            <button onclick="fillAuth('provider@example.com')" class="px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-700 text-left transition">
                                <strong class="block text-white">Provider User</strong>
                                provider@example.com
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950/50 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} Grab A Hand Engine. All rights reserved.
    </footer>

    <script>
        function fillAuth(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'password';
        }
    </script>
</body>
</html>
