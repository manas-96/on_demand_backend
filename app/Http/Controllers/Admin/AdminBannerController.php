<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBannerController extends Controller
{
    /**
     * Display a paginated listing of promotional banners.
     */
    public function index(Request $request): View
    {
        $query = Banner::with(['category', 'subCategory']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('highlight_text', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('service_category_id', $categoryId);
        }

        $banners = $query->ordered()->paginate(15);
        $categories = ServiceCategory::parents()->get();

        return view('admin.banners.index', compact('banners', 'categories'));
    }

    /**
     * Show form to create a new banner.
     */
    public function create(): View
    {
        $parents = ServiceCategory::parents()->with('subcategories')->get();
        $subcategories = ServiceCategory::onlySubcategories()->get();

        return view('admin.banners.create', compact('parents', 'subcategories'));
    }

    /**
     * Store a newly created banner in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'highlight_text' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'sub_category_id' => ['nullable', 'exists:service_categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('banners', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $banner = Banner::create([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'highlight_text' => $validated['highlight_text'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'service_category_id' => $validated['service_category_id'] ?? null,
            'sub_category_id' => $validated['sub_category_id'] ?? null,
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.banners.index')
            ->with('success', "Banner '{$banner->title}' created successfully.");
    }

    /**
     * Show form to edit an existing banner.
     */
    public function edit(int $id): View
    {
        $banner = Banner::findOrFail($id);
        $parents = ServiceCategory::parents()->with('subcategories')->get();
        $subcategories = ServiceCategory::onlySubcategories()->get();

        return view('admin.banners.edit', compact('banner', 'parents', 'subcategories'));
    }

    /**
     * Update the specified banner in database.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'highlight_text' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'sub_category_id' => ['nullable', 'exists:service_categories,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $banner->image;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('banners', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $banner->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'highlight_text' => $validated['highlight_text'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'service_category_id' => $validated['service_category_id'] ?? null,
            'sub_category_id' => $validated['sub_category_id'] ?? null,
            'image' => $imagePath,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.banners.index')
            ->with('success', "Banner '{$banner->title}' updated successfully.");
    }

    /**
     * Delete specified banner from database.
     */
    public function destroy(int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);
        $title = $banner->title;
        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', "Banner '{$title}' deleted successfully.");
    }

    /**
     * Toggle active status of a banner.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => ! $banner->is_active]);

        $statusStr = $banner->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Banner '{$banner->title}' has been {$statusStr}.");
    }
}
