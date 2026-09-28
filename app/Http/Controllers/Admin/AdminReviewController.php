<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::with(['customer', 'provider', 'technician', 'serviceRequest'])
            ->latest()
            ->paginate(15);

        return view('admin.reviews.index', compact('reviews'));
    }
}
