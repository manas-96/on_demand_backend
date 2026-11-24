<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicianProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTechnicianController extends Controller
{
    public function index(Request $request): View
    {
        $query = TechnicianProfile::with(['user', 'provider', 'providerProfile']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                    ->orWhereHas('provider', function ($providerQuery) use ($search) {
                        $providerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('providerProfile', function ($providerProfileQuery) use ($search) {
                        $providerProfileQuery->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        $technicians = $query->latest()->paginate(15);

        return view('admin.technicians.index', compact('technicians'));
    }
}
