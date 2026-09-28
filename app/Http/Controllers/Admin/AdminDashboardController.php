<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Get platform admin statistics, charts, and lifecycle metrics.
     */
    public function dashboard(Request $request): JsonResponse|View
    {
        $totalUsers = User::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProviders = ProviderProfile::count();
        $approvedProviders = ProviderProfile::where('is_approved', true)->count();
        $totalTechnicians = User::where('role', 'technician')->count();
        $totalRequests = ServiceRequest::count();
        $activeJobs = ServiceRequest::whereIn('status', ['awarded', 'assigned', 'in_progress'])->count();
        $completedJobs = ServiceRequest::where('status', 'completed')->count();
        $totalRevenue = (float) Invoice::where('status', 'paid')->sum('total');
        $totalPlatformFees = (float) ProviderEarning::sum('platform_fee');
        $totalNetPayouts = (float) ProviderEarning::sum('net_amount');

        // Chart Data 1: Most Booked Service Categories
        $mostBookedCategories = ServiceCategory::withCount('serviceRequests')
            ->get()
            ->map(function ($cat) {
                return [
                    'category' => $cat->name,
                    'count' => (int) $cat->service_requests_count,
                ];
            });

        // Chart Data 2: Request Status Distribution
        $statusDistribution = [
            'Completed' => ServiceRequest::where('status', 'completed')->count(),
            'In Progress' => ServiceRequest::where('status', 'in_progress')->count(),
            'Awarded/Assigned' => ServiceRequest::whereIn('status', ['awarded', 'assigned'])->count(),
            'In Negotiation' => ServiceRequest::where('status', 'in_negotiation')->count(),
            'Submitted' => ServiceRequest::whereIn('status', ['submitted', 'matching', 'invited'])->count(),
        ];

        // Chart Data 3: Driver-agnostic Revenue Trend (MySQL & SQLite compatible)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $revenueTrend = ProviderEarning::select(
                DB::raw('strftime("%m-%Y", created_at) as month'),
                DB::raw('SUM(gross_amount) as gross'),
                DB::raw('SUM(platform_fee) as fee'),
                DB::raw('SUM(net_amount) as net')
            )
                ->groupBy('month')
                ->get();
        } else {
            $revenueTrend = ProviderEarning::select(
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
                DB::raw('SUM(gross_amount) as gross'),
                DB::raw('SUM(platform_fee) as fee'),
                DB::raw('SUM(net_amount) as net')
            )
                ->groupBy('month')
                ->orderBy(DB::raw('MIN(created_at)'), 'asc')
                ->get();
        }

        if ($revenueTrend->isEmpty()) {
            $revenueTrend = collect([
                ['month' => now()->format('b Y'), 'gross' => $totalRevenue, 'fee' => $totalPlatformFees, 'net' => $totalNetPayouts],
            ]);
        }

        // Recent Service Bookings
        $recentBookings = ServiceRequest::with(['service', 'category', 'customer'])
            ->latest()
            ->take(6)
            ->get();

        // Top Provider Companies
        $earningsByProvider = ProviderEarning::select('provider_id', DB::raw('SUM(net_amount) as total_net'))
            ->groupBy('provider_id')
            ->pluck('total_net', 'provider_id');

        $completedJobsByProvider = ProviderJobRequest::where('status', 'selected')
            ->whereHas('serviceRequest', function ($q) {
                $q->where('status', 'completed');
            })
            ->select('provider_id', DB::raw('COUNT(*) as count'))
            ->groupBy('provider_id')
            ->pluck('count', 'provider_id');

        $topProviders = ProviderProfile::with('user')
            ->get()
            ->map(function ($p) use ($earningsByProvider, $completedJobsByProvider) {
                $p->net_earnings = (float) ($earningsByProvider[$p->user_id] ?? 0);
                $p->completed_count = (int) ($completedJobsByProvider[$p->user_id] ?? 0);

                return $p;
            })
            ->sortByDesc('net_earnings')
            ->take(5);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact(
                    'totalUsers',
                    'totalCustomers',
                    'totalProviders',
                    'totalTechnicians',
                    'totalRequests',
                    'activeJobs',
                    'completedJobs',
                    'totalRevenue',
                    'totalPlatformFees',
                    'mostBookedCategories',
                    'statusDistribution',
                    'revenueTrend'
                ),
            ]);
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalCustomers',
            'totalProviders',
            'approvedProviders',
            'totalTechnicians',
            'totalRequests',
            'activeJobs',
            'completedJobs',
            'totalRevenue',
            'totalPlatformFees',
            'totalNetPayouts',
            'mostBookedCategories',
            'statusDistribution',
            'revenueTrend',
            'recentBookings',
            'topProviders'
        ));
    }
}
