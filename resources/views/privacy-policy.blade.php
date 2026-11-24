<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Grab A Hand Service Marketplace</title>
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
                    <span class="text-[10px] font-semibold text-amber-400 uppercase tracking-widest block mt-0.5">Service Marketplace</span>
                </div>
            </a>
            
            <div class="flex items-center space-x-4 text-xs font-semibold">
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 transition">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold transition">Sign In</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-12 flex-1 w-full">
        <div class="p-8 sm:p-12 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl space-y-8">
            <div class="border-b border-slate-800 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block mb-1">Legal Document</span>
                    <h1 class="text-3xl font-extrabold text-white tracking-tight">Privacy Policy</h1>
                </div>
                <div class="text-xs text-slate-400 sm:text-right">
                    <span class="block">Last Modified:</span>
                    <strong class="text-slate-200">{{ $updatedAt?->inDisplayTimezone()->format('F d, Y') ?? 'September 2026' }}</strong>
                </div>
            </div>

            <!-- Content Container -->
            <div class="prose prose-invert max-w-none text-slate-300 text-sm leading-relaxed space-y-6">
                {/* Format content line breaks and headers gracefully */}
                @foreach(explode("\n\n", $privacyPolicy) as $paragraph)
                    @if(str_starts_with(trim($paragraph), '# '))
                        <h1 class="text-2xl font-bold text-white mt-8 mb-4 border-b border-slate-800 pb-2">{{ substr(trim($paragraph), 2) }}</h1>
                    @elseif(str_starts_with(trim($paragraph), '## '))
                        <h2 class="text-lg font-bold text-amber-400 mt-6 mb-3">{{ substr(trim($paragraph), 3) }}</h2>
                    @elseif(str_starts_with(trim($paragraph), '---'))
                        <hr class="border-slate-800 my-6">
                    @else
                        <p class="whitespace-pre-line text-slate-300 leading-relaxed">{{ trim($paragraph) }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-900/60 py-6 text-center text-xs text-slate-500">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>© {{ date('Y') }} Grab A Hand Service Marketplace Inc. All rights reserved.</div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('privacy-policy') }}" class="text-amber-400 hover:underline">Privacy Policy</a>
                <span>•</span>
                <a href="#" class="text-slate-400 hover:underline">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>
