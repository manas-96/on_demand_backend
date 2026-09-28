<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Provider - ServicePlatform</title>
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
            <a href="{{ route('landing') }}" class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-400 to-yellow-500 flex items-center justify-center font-bold text-slate-950 shadow-lg shadow-amber-500/30">
                    SP
                </div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
                    ServicePlatform
                </span>
            </a>
            <a href="{{ route('landing') }}" class="text-xs font-semibold px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition">
                &larr; Back to Sign In
            </a>
        </div>
    </header>

    <!-- Registration Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-1 w-full">
        <div class="space-y-6">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-400/10 text-amber-400 border border-amber-400/20 text-xs font-semibold">
                    <span>🤝 Join Our Partner Network</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Register as a Service Provider</h1>
                <p class="text-sm text-slate-400 max-w-xl mx-auto">Create your provider profile to receive local customer job requests, accept jobs, and manage your technician fleet.</p>
            </div>

            <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-6 md:p-8 shadow-2xl shadow-amber-950/20 backdrop-blur">
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <div>• {{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register.provider.post') }}" class="space-y-6">
                    @csrf

                    <!-- Section 1: Business Details -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">1. Company & Business Profile</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="company_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Company Name *</label>
                                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required placeholder="e.g. Apex Home Services" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>

                            <div>
                                <label for="business_registration" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Business Registration #</label>
                                <input type="text" id="business_registration" name="business_registration" value="{{ old('business_registration') }}" placeholder="e.g. REG-2026-0099" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Full Operating Address *</label>
                            <textarea id="address" name="address" rows="2" required placeholder="e.g. 742 Evergreen Terrace, Suite 100, Sector 12" 
                                      class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">{{ old('address') }}</textarea>
                        </div>

                        <div>
                            <label for="service_radius_km" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Operating Service Radius (km) *</label>
                            <input type="number" step="0.5" min="1" max="500" id="service_radius_km" name="service_radius_km" value="{{ old('service_radius_km', 25) }}" required 
                                   class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                        </div>
                    </div>

                    <!-- Section 2: Contact & Account -->
                    <div class="space-y-4 pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">2. Primary Contact & Account Access</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Contact Name *</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Robert Smith" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. +1 (555) 234-5678" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Login Email Address *</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. provider@company.com" 
                                   class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password *</label>
                                <input type="password" id="password" name="password" required placeholder="Minimum 8 characters" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm Password *</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter password" 
                                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Offered Service Categories -->
                    @if(isset($categories) && $categories->count() > 0)
                        <div class="space-y-4 pt-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">3. Service Categories Offered</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
                                @foreach($categories as $category)
                                    <label class="flex items-center space-x-3 p-3 rounded-xl bg-slate-900 border border-slate-700 cursor-pointer hover:border-amber-400/50 transition">
                                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" 
                                               {{ is_array(old('categories')) && in_array($category->id, old('categories')) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded text-amber-400 bg-slate-950 border-slate-700 focus:ring-amber-400">
                                        <div class="flex items-center space-x-3">
                                            @if($category->image)
                                                @if(Str::startsWith($category->image, ['http://', 'https://']))
                                                    <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-8 h-8 object-cover rounded-lg border border-slate-700">
                                                @else
                                                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-8 h-8 object-cover rounded-lg border border-slate-700">
                                                @endif
                                            @else
                                                <div class="w-8 h-8 rounded-lg bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-xs font-bold">
                                                    {{ strtoupper(substr($category->name, 0, 2)) }}
                                                </div>
                                            @endif
                                            <span class="text-sm font-semibold text-white">{{ $category->name }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Submit -->
                    <div class="pt-4 border-t border-slate-700/80">
                        <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition duration-200 text-sm">
                            Register Provider Account
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    Already registered as a Provider? 
                    <a href="{{ route('landing') }}" class="text-amber-400 font-semibold hover:underline">Sign In here</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950/50 py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} ServicePlatform On-Demand Backend Engine. All rights reserved.
    </footer>

</body>
</html>
