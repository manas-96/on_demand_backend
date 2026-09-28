<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\ProviderBid;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\TechnicianProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProviderDashboardController extends Controller
{
    /**
     * Get Provider company dashboard stats.
     */
    public function dashboard(Request $request): JsonResponse|View
    {
        $provider = $request->user();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_technicians' => TechnicianProfile::where('provider_id', $provider->id)->count(),
                    'active_bids' => ProviderBid::where('provider_id', $provider->id)->whereIn('status', ['submitted', 'under_negotiation'])->count(),
                    'won_jobs' => ProviderBid::where('provider_id', $provider->id)->where('status', 'accepted')->count(),
                    'assigned_jobs' => JobAssignment::where('provider_id', $provider->id)->where('status', 'assigned')->count(),
                    'completed_jobs' => JobAssignment::where('provider_id', $provider->id)->where('status', 'completed')->count(),
                    'total_net_earnings' => ProviderEarning::where('provider_id', $provider->id)->sum('net_amount'),
                ],
            ]);
        }

        // Chart Data 1: Provider Monthly Earnings Trend
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $earningsTrend = ProviderEarning::where('provider_id', $provider->id)
                ->select(
                    DB::raw('strftime("%m-%Y", created_at) as month'),
                    DB::raw('SUM(gross_amount) as gross'),
                    DB::raw('SUM(platform_fee) as fee'),
                    DB::raw('SUM(net_amount) as net')
                )
                ->groupBy('month')
                ->get();
        } else {
            $earningsTrend = ProviderEarning::where('provider_id', $provider->id)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
                    DB::raw('SUM(gross_amount) as gross'),
                    DB::raw('SUM(platform_fee) as fee'),
                    DB::raw('SUM(net_amount) as net')
                )
                ->groupBy('month')
                ->orderBy(DB::raw('MIN(created_at)'), 'asc')
                ->get();
        }

        if ($earningsTrend->isEmpty()) {
            $earningsTrend = collect([
                [
                    'month' => now()->format('M Y'),
                    'gross' => (float) ProviderEarning::where('provider_id', $provider->id)->sum('gross_amount'),
                    'fee' => (float) ProviderEarning::where('provider_id', $provider->id)->sum('platform_fee'),
                    'net' => (float) ProviderEarning::where('provider_id', $provider->id)->sum('net_amount'),
                ],
            ]);
        }

        // Chart Data 2: Provider Bidding Conversion Distribution
        $bidStatusDistribution = [
            'Accepted / Won' => ProviderBid::where('provider_id', $provider->id)->where('status', 'accepted')->count(),
            'Submitted / Active' => ProviderBid::where('provider_id', $provider->id)->where('status', 'submitted')->count(),
            'Under Negotiation' => ProviderBid::where('provider_id', $provider->id)->where('status', 'under_negotiation')->count(),
            'Rejected / Expired' => ProviderBid::where('provider_id', $provider->id)->whereIn('status', ['rejected', 'expired'])->count(),
        ];

        // Chart Data 3: Provider Job Execution Status Breakdown
        $jobStatusDistribution = [
            'Assigned' => JobAssignment::where('provider_id', $provider->id)->where('status', 'assigned')->count(),
            'Accepted' => JobAssignment::where('provider_id', $provider->id)->where('status', 'accepted')->count(),
            'In Progress' => JobAssignment::where('provider_id', $provider->id)->whereIn('status', ['arrived', 'inspecting', 'quoted', 'in_progress'])->count(),
            'Completed' => JobAssignment::where('provider_id', $provider->id)->where('status', 'completed')->count(),
        ];

        // Incoming Job Requests for this provider
        $incomingRequests = ProviderJobRequest::has('serviceRequest')
            ->with(['serviceRequest.category', 'serviceRequest.service', 'serviceRequest.customer'])
            ->where('provider_id', $provider->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereDoesntHave('serviceRequest.jobAssignments', function ($q) use ($provider) {
                $q->where('provider_id', $provider->id);
            })
            ->latest()
            ->take(6)
            ->get();

        // Recent Technician Assignments
        $recentAssignments = JobAssignment::has('serviceRequest')
            ->with(['serviceRequest', 'technician'])
            ->where('provider_id', $provider->id)
            ->latest()
            ->take(5)
            ->get();

        return view('provider.dashboard', compact('earningsTrend', 'bidStatusDistribution', 'jobStatusDistribution', 'incomingRequests', 'recentAssignments'));
    }
}
