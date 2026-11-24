<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminServiceController extends Controller
{
    /**
     * Display list of Service Categories (Parents) for Admin.
     */
    public function index(Request $request): View
    {
        $query = ServiceCategory::parents()->withCount('subcategories');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $categories = $query->latest()->paginate(15);

        return view('admin.services.index', compact('categories'));
    }

    /**
     * Display subcategories for a specific parent category.
     */
    public function show(int $id): View
    {
        $category = ServiceCategory::with('subcategories')->findOrFail($id);

        return view('admin.services.show', compact('category'));
    }

    /**
     * Show form to create a new Service Category or Subcategory.
     */
    public function create(Request $request): View
    {
        $parents = ServiceCategory::parents()->get();
        $selectedParentId = $request->get('parent_id');

        return view('admin.services.create', compact('parents', 'selectedParentId'));
    }

    /**
     * Store a new Service Category with name, parent, and image.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories,name'],
            'parent_id' => ['nullable', 'exists:service_categories,id'],
            'minimum_booking_charge' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'image_url' => ['nullable', 'string', 'max:500'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $slug = Str::slug($validated['name']);
        $tags = $this->parseTags($request->input('tags'));

        $category = ServiceCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'minimum_booking_charge' => $validated['minimum_booking_charge'] ?? 0.00,
            'tags' => $tags,
            'image' => $imagePath,
            'is_active' => true,
        ]);

        // Auto-create base service entry under category for model relation compatibility
        Service::create([
            'service_category_id' => $category->id,
            'name' => $category->name.' Service',
            'slug' => $slug.'-service',
            'description' => 'General '.$category->name.' Service',
            'base_price' => 0.00,
            'is_active' => true,
        ]);

        $redirectRoute = $category->parent_id
            ? route('admin.services.show', $category->parent_id)
            : route('admin.services.index');

        return redirect($redirectRoute)
            ->with('success', "'{$category->name}' created successfully.");
    }

    /**
     * Show form to edit a Service Category.
     */
    public function edit(int $id): View
    {
        $category = ServiceCategory::findOrFail($id);
        $parents = ServiceCategory::parents()->where('id', '!=', $id)->get();

        return view('admin.services.edit', compact('category', 'parents'));
    }

    /**
     * Update an existing Service Category.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $category = ServiceCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories,name,'.$id],
            'parent_id' => ['nullable', 'exists:service_categories,id'],
            'minimum_booking_charge' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'image_url' => ['nullable', 'string', 'max:500'],
        ]);

        $imagePath = $category->image;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $tags = $this->parseTags($request->input('tags'));

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'minimum_booking_charge' => $validated['minimum_booking_charge'] ?? 0.00,
            'tags' => $tags,
            'image' => $imagePath,
        ]);

        $redirectRoute = $category->parent_id
            ? route('admin.services.show', $category->parent_id)
            : route('admin.services.index');

        return redirect($redirectRoute)
            ->with('success', "'{$category->name}' updated successfully.");
    }

    /**
     * Parse a comma-separated tags string or array into a clean unique list of tags.
     *
     * @return array<int, string>
     */
    private function parseTags(mixed $tagsInput): array
    {
        if (is_array($tagsInput)) {
            $rawList = $tagsInput;
        } elseif (is_string($tagsInput) && trim($tagsInput) !== '') {
            $rawList = explode(',', $tagsInput);
        } else {
            return [];
        }

        $cleaned = [];
        foreach ($rawList as $tag) {
            $trimmed = strtolower(trim((string) $tag));
            if ($trimmed !== '' && ! in_array($trimmed, $cleaned, true)) {
                $cleaned[] = $trimmed;
            }
        }

        return $cleaned;
    }

    /**
     * Toggle active/inactive status of a Service Category.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $category = ServiceCategory::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        $statusStr = $category->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Service category '{$category->name}' has been {$statusStr}.");
    }
}
