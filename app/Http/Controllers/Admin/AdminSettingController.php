<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobStatusHistory;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    /**
     * Display settings overview, configuration form, and audit logs.
     */
    public function index(Request $request): View
    {
        $settings = SystemSetting::getSettings();
        $auditLogs = JobStatusHistory::with(['serviceRequest', 'changedBy'])
            ->latest()
            ->paginate(15);

        return view('admin.settings.index', compact('settings', 'auditLogs'));
    }

    /**
     * Update system fee, tax, and minimum booking charge settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_fee' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_booking_charge' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);

        $settings = SystemSetting::getSettings();
        $settings->update($validated);

        return back()->with('success', 'System operational settings updated successfully.');
    }
}
