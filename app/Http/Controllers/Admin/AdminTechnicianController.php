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
        $query = TechnicianProfile::with(['user', 'provider']);

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $technicians = $query->latest()->paginate(15);

        return view('admin.technicians.index', compact('technicians'));
    }
}
