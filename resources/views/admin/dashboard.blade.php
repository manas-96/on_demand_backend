@extends('layouts.admin')

@section('title', 'Platform Analytics & Control')

@section('content')
<!-- Include Chart.js via CDN for interactive animated charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    
    <!-- Hero Banner with Glow -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-950/60 via-slate-900 to-yellow-950/50 border border-slate-800 p-8 shadow-2xl shadow-amber-950/30">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 -top-10 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-400/20 text-amber-300 border border-amber-400/30 text-xs font-semibold mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live Operations Engine</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Executive Control Center</h1>
                <p class="text-sm text-slate-300 max-w-2xl mt-1.5 leading-relaxed">
                    Real-time oversight across <strong>{{ $totalProviders }} Provider Companies</strong>, <strong>{{ $totalTechnicians }} Field Technicians</strong>, and <strong>{{ $totalCustomers }} Customers</strong>.
                </p>
            </div>

            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('admin.requests.index') }}" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Inspect Requests</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 High-Impact KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Settled Revenue -->
        <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800/80 shadow-xl relative overflow-hidden group hover:border-amber-400/50 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-white tracking-tight">${{ number_format($totalRevenue, 2) }}</div>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="text-xs font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">+100% Verified</span>
                    <span class="text-xs text-slate-500">Total Settled Invoices</span>
                </div>
            </div>
        </div>

        <!-- Platform Commission Earnings -->
        <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800/80 shadow-xl relative overflow-hidden group hover:border-amber-400/50 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Platform Fee</span>
                <div class="w-10 h-10 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-amber-400 tracking-tight">${{ number_format($totalPlatformFees, 2) }}</div>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="text-xs font-semibold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded-full border border-amber-400/20">Net Commission</span>
                    <span class="text-xs text-slate-500">Platform Revenue</span>
                </div>
            </div>
        </div>

        <!-- Active Jobs in Execution -->
        <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800/80 shadow-xl relative overflow-hidden group hover:border-amber-400/50 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Bookings</span>
                <div class="w-10 h-10 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-white tracking-tight">{{ $activeJobs }}</div>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="text-xs font-semibold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded-full border border-amber-400/20">{{ $totalRequests }} Total Bookings</span>
                </div>
            </div>
        </div>

        <!-- Approved Provider Companies -->
        <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800/80 shadow-xl relative overflow-hidden group hover:border-amber-400/50 transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Provider Network</span>
                <div class="w-10 h-10 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m3 0h1m-1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-white tracking-tight">{{ $approvedProviders }} <span class="text-sm font-normal text-slate-500">/ {{ $totalProviders }}</span></div>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="text-xs font-semibold text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded-full border border-amber-400/20">{{ $totalTechnicians }} Technicians</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Section: Revenue Trend & Most Booked Services -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Revenue & Platform Fee Growth Chart (8 cols) -->
        <div class="lg:col-span-8 p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl flex flex-col justify-between">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-white tracking-tight">Revenue & Platform Commission Trend</h2>
                    <p class="text-xs text-slate-400">Monthly gross booking totals vs 10% platform commission fee</p>
                </div>
                <div class="flex items-center space-x-3 text-xs">
                    <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-full bg-amber-400"></span><span class="text-slate-300">Gross Revenue</span></span>
                    <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500"></span><span class="text-slate-300">Platform Commission</span></span>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Right: Most Booked Services Bar Chart (4 cols) -->
        <div class="lg:col-span-4 p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl flex flex-col justify-between">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white tracking-tight">Most Booked Categories</h2>
                <p class="text-xs text-slate-400">Booking volume breakdown by service sector</p>
            </div>
            <div class="relative h-72 w-full flex items-center justify-center">
                <canvas id="categoriesChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Middle Section: Request Status Distribution & Top Provider Companies -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Request Lifecycle Status Distribution (5 cols) -->
        <div class="lg:col-span-5 p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl flex flex-col justify-between">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white tracking-tight">Request Lifecycle Breakdown</h2>
                <p class="text-xs text-slate-400">Distribution of requests across state machine stages</p>
            </div>
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Top Performing Provider Companies (7 cols) -->
        <div class="lg:col-span-7 p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-white tracking-tight">Top Service Provider Companies</h2>
                    <p class="text-xs text-slate-400">Ranked by net earnings & verified completed contracts</p>
                </div>
                <a href="{{ route('admin.providers.index') }}" class="text-xs text-amber-400 hover:underline font-semibold">View All Providers →</a>
            </div>

            <div class="space-y-3">
                @forelse($topProviders as $index => $provider)
                    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/80 flex items-center justify-between hover:border-slate-700 transition duration-200">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 font-bold text-amber-400 text-sm flex items-center justify-center shrink-0">
                                #{{ $index + 1 }}
                            </div>
                            <div>
                                <div class="font-bold text-white text-sm">{{ $provider->company_name }}</div>
                                <div class="text-xs text-slate-400">{{ $provider->user->email }} • Radius: {{ $provider->service_radius_km }}km</div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm font-extrabold text-emerald-400">${{ number_format($provider->net_earnings, 2) }}</div>
                            <div class="text-[11px] text-slate-500">{{ $provider->completed_count }} Jobs Completed</div>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm py-4 text-center">No provider profiles active yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Recent Service Requests Live Activity Stream -->
    <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight">Recent Service Bookings Stream</h2>
                <p class="text-xs text-slate-400">Live operational feed of incoming customer service requests</p>
            </div>
            <a href="{{ route('admin.requests.index') }}" class="px-4 py-2 rounded-xl bg-amber-400/10 hover:bg-amber-400/20 text-amber-400 border border-amber-400/30 text-xs font-semibold transition">
                View Full Registry →
            </a>
        </div>

        <div class="rounded-2xl bg-slate-950 border border-slate-800/80 overflow-hidden">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Booking Number</th>
                        <th class="px-6 py-4">Service Required</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Current Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($recentBookings as $b)
                        <tr class="hover:bg-slate-900/50 transition">
                            <td class="px-6 py-4 font-bold text-white">
                                {{ $b->request_number }}
                                <div class="text-[11px] text-slate-500 font-normal">{{ $b->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-200 font-medium">
                                {{ $b->service->name ?? $b->category->name ?? 'General Service' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-300">
                                {{ $b->customer->name ?? 'Customer' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                    {{ ucfirst($b->request_type->value) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                    {{ ucfirst(str_replace('_', ' ', $b->status->value)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.requests.show', $b->id) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300">Inspect →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No recent bookings recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Render Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // 1. Revenue & Platform Fee Trend Line Chart
        const revenueTrendData = @json($revenueTrend);
        const months = revenueTrendData.map(d => d.month);
        const grossAmounts = revenueTrendData.map(d => parseFloat(d.gross || 0));
        const feeAmounts = revenueTrendData.map(d => parseFloat(d.fee || 0));

        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        const gradGross = ctxRev.createLinearGradient(0, 0, 0, 300);
        gradGross.addColorStop(0, 'rgba(255, 212, 71, 0.4)');
        gradGross.addColorStop(1, 'rgba(255, 212, 71, 0.0)');

        const gradFee = ctxRev.createLinearGradient(0, 0, 0, 300);
        gradFee.addColorStop(0, 'rgba(246, 177, 0, 0.4)');
        gradFee.addColorStop(1, 'rgba(246, 177, 0, 0.0)');

        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Gross Revenue ($)',
                        data: grossAmounts,
                        borderColor: '#ffd447',
                        backgroundColor: gradGross,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                    },
                    {
                        label: 'Platform Commission ($)',
                        data: feeAmounts,
                        borderColor: '#f6b100',
                        backgroundColor: gradFee,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)' },
                        ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)' },
                        ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });

        // 2. Most Booked Service Categories Horizontal Bar Chart
        const categoriesData = @json($mostBookedCategories);
        const catLabels = categoriesData.map(c => c.category);
        const catCounts = categoriesData.map(c => c.count);

        const ctxCat = document.getElementById('categoriesChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Bookings Count',
                    data: catCounts,
                    backgroundColor: ['#ffd447', '#f6b100', '#e59b00', '#f59e0b', '#10b981'],
                    borderRadius: 8,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)' },
                        ticks: { color: '#94a3b8', stepSize: 1 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#f8fafc', font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' } }
                    }
                }
            }
        });

        // 3. Request Status Distribution Doughnut Chart
        const statusData = @json($statusDistribution);
        const statusLabels = Object.keys(statusData);
        const statusValues = Object.values(statusData);

        const ctxStat = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStat, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: ['#10b981', '#ffd447', '#f6b100', '#e59b00', '#64748b'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#cbd5e1', font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                },
                cutout: '70%'
            }
        });

    });
</script>
@endsection
