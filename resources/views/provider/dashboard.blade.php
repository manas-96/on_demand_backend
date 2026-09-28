@extends('layouts.provider')

@section('title', 'Company Dashboard')

@section('content')
<div class="space-y-8">
    
    <!-- Welcome Banner -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-yellow-950/30 border border-slate-800 flex items-center justify-between shadow-xl">
        <div>
            <h1 class="text-2xl font-bold text-white">Service Provider Console</h1>
            <p class="text-sm text-slate-400 mt-1">Manage company services, technician dispatches, and net earnings ledger.</p>
        </div>
        <span class="px-4 py-2 rounded-xl bg-amber-400/10 text-amber-400 border border-amber-400/20 text-xs font-semibold">
            Provider ID #{{ auth()->user()->id }}
        </span>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Company Technicians</span>
            <div class="text-3xl font-extrabold text-white">{{ \App\Models\TechnicianProfile::where('provider_id', auth()->id())->count() }}</div>
            <div class="text-xs text-amber-400">Active Field Workers</div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Dispatches</span>
            <div class="text-3xl font-extrabold text-white">{{ \App\Models\ProviderJobRequest::where('provider_id', auth()->id())->whereIn('status', ['sent', 'viewed'])->whereDoesntHave('serviceRequest.jobAssignments', function ($q) { $q->where('provider_id', auth()->id()); })->count() }}</div>
            <div class="text-xs text-amber-400">Incoming Requests</div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Accepted / Active Jobs</span>
            <div class="text-3xl font-extrabold text-white">{{ \App\Models\JobAssignment::where('provider_id', auth()->id())->count() }}</div>
            <div class="text-xs text-emerald-400">Active Contracts</div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Net Earnings</span>
            <div class="text-3xl font-extrabold text-white">${{ number_format(\App\Models\ProviderEarning::where('provider_id', auth()->id())->sum('net_amount'), 2) }}</div>
            <div class="text-xs text-amber-400">After Platform Commission</div>
        </div>
    </div>

    <!-- New Incoming Job Requests Section -->
    <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                </div>
                <h2 class="text-lg font-bold text-white tracking-tight">New Incoming Job Requests</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-amber-400/20 text-amber-400 border border-amber-400/30">
                    {{ $incomingRequests->count() }} Pending
                </span>
            </div>
            <a href="{{ route('provider.requests.index') }}" class="text-xs font-semibold text-amber-400 hover:underline">
                View All Requests &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($incomingRequests as $req)
                @if($req->serviceRequest)
                    <a href="{{ route('provider.requests.show', $req->id) }}" 
                       class="group block p-5 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-amber-400/50 hover:bg-slate-800/40 transition duration-200 shadow-md">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-amber-400 bg-amber-400/10 px-2.5 py-1 rounded-lg border border-amber-400/20">
                                {{ $req->serviceRequest?->request_number ?? 'SR-'.$req->service_request_id }}
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ $req->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">
                            {{ $req->serviceRequest?->category?->name ?? $req->serviceRequest?->service?->name ?? 'Service Request' }}
                        </h3>

                        <p class="text-xs text-slate-400 mt-2 line-clamp-2">
                            {{ $req->serviceRequest?->description ?? 'Service evaluation requested.' }}
                        </p>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                            <div class="text-slate-300 flex items-center space-x-1 truncate max-w-[180px]">
                                <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                <span class="truncate">{{ $req->serviceRequest?->address_line ?? 'Customer Location' }}</span>
                            </div>
                            <span class="text-amber-400 font-bold group-hover:translate-x-1 transition-transform">
                                View & Accept &rarr;
                            </span>
                        </div>
                    </a>
                @endif
            @empty
                <div class="col-span-full py-8 text-center text-slate-500 text-sm bg-slate-950/40 rounded-2xl border border-slate-800/60">
                    No pending incoming job requests at this moment.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Provider Revenue & Net Payout Growth Chart -->
    <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl flex flex-col justify-between">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight">Revenue & Net Payout Trend</h2>
                <p class="text-xs text-slate-400">Monthly gross earnings vs net payout after commission</p>
            </div>
            <div class="flex items-center space-x-4 text-xs">
                <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-full bg-amber-400"></span><span class="text-slate-300">Gross Revenue</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-3 h-3 rounded-full bg-emerald-400"></span><span class="text-slate-300">Net Payout</span></span>
            </div>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="providerEarningsChart"></canvas>
        </div>
    </div>

    <!-- Job Assignment Status Breakdown -->
    <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800/80 shadow-2xl">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-white tracking-tight">Job Execution Status Distribution</h2>
            <p class="text-xs text-slate-400">Active technician assignments across execution workflow stages</p>
        </div>
        <div class="relative h-64 w-full">
            <canvas id="providerJobsChart"></canvas>
        </div>
    </div>

    <!-- Management Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Employed Technicians -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <h2 class="text-lg font-bold text-white mb-4">Employed Technicians</h2>
            <div class="space-y-3">
                @forelse(\App\Models\TechnicianProfile::with('user')->where('provider_id', auth()->id())->get() as $tech)
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-white">{{ $tech->user->name }}</div>
                            <div class="text-xs text-slate-400">{{ $tech->qualification ?? 'General Technician' }} • {{ $tech->user->email }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $tech->duty_status === 'on_duty' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                            {{ ucfirst(str_replace('_', ' ', $tech->duty_status)) }}
                        </span>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">No technicians added yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Technician Assignments -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <h2 class="text-lg font-bold text-white mb-4">Recent Technician Assignments</h2>
            <div class="space-y-3">
                @forelse($recentAssignments ?? [] as $assign)
                    @if($assign->serviceRequest)
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-white">{{ $assign->serviceRequest?->request_number ?? 'JOB-'.$assign->id }} • {{ $assign->technician?->name ?? 'Technician' }}</div>
                            <div class="text-xs text-slate-400">Assigned {{ $assign->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ ucfirst(is_object($assign->status) ? $assign->status->value : (string) $assign->status) }}
                        </span>
                    </div>
                    @endif
                @empty
                    <p class="text-slate-500 text-sm">No technician assignments yet.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.font.family = 'Plus Jakarta Sans, sans-serif';

        // 1. Monthly Revenue & Net Payout Trend Chart
        const earningsTrendData = @json($earningsTrend ?? []);
        const months = earningsTrendData.map(d => d.month);
        const grossAmounts = earningsTrendData.map(d => parseFloat(d.gross || 0));
        const netAmounts = earningsTrendData.map(d => parseFloat(d.net || 0));

        const ctxEarnings = document.getElementById('providerEarningsChart').getContext('2d');
        const gradGross = ctxEarnings.createLinearGradient(0, 0, 0, 300);
        gradGross.addColorStop(0, 'rgba(255, 212, 71, 0.4)');
        gradGross.addColorStop(1, 'rgba(255, 212, 71, 0.0)');

        const gradNet = ctxEarnings.createLinearGradient(0, 0, 0, 300);
        gradNet.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
        gradNet.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        new Chart(ctxEarnings, {
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
                        label: 'Net Payout ($)',
                        data: netAmounts,
                        borderColor: '#10b981',
                        backgroundColor: gradNet,
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
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)' },
                        ticks: { color: '#94a3b8', font: { size: 11 } }
                    }
                }
            }
        });

        // 2. Job Execution Status Distribution Bar Chart
        const jobData = @json($jobStatusDistribution ?? []);
        const jobLabels = Object.keys(jobData);
        const jobValues = Object.values(jobData);

        const ctxJobs = document.getElementById('providerJobsChart').getContext('2d');
        new Chart(ctxJobs, {
            type: 'bar',
            data: {
                labels: jobLabels,
                datasets: [{
                    label: 'Number of Jobs',
                    data: jobValues,
                    backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981'],
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#f8fafc', font: { size: 11, weight: 'bold' } }
                    },
                    y: {
                        grid: { color: 'rgba(51, 65, 85, 0.3)' },
                        ticks: { color: '#94a3b8', stepSize: 1 }
                    }
                }
            }
        });
    });
</script>
@endsection
