<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ProviderEarning;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFinancialController extends Controller
{
    public function invoices(Request $request): View
    {
        $invoices = Invoice::with(['customer', 'provider', 'serviceRequest', 'payments'])
            ->latest()
            ->paginate(15);

        return view('admin.financials.invoices', compact('invoices'));
    }

    public function earnings(Request $request): View
    {
        $earnings = ProviderEarning::with(['provider', 'serviceRequest', 'invoice'])
            ->latest()
            ->paginate(15);

        return view('admin.financials.earnings', compact('earnings'));
    }
}
