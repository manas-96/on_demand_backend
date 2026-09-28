<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionalVideo;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPromotionalVideoController extends Controller
{
    /**
     * Display listing of promotional videos.
     */
    public function index(Request $request): View
    {
        $query = PromotionalVideo::with('category');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('service_category_id', $categoryId);
        }

        $videos = $query->ordered()->paginate(15);
        $categories = ServiceCategory::parents()->get();

        return view('admin.videos.index', compact('videos', 'categories'));
    }

    /**
     * Show form to create a new promotional video.
     */
    public function create(): View
    {
        $categories = ServiceCategory::parents()->get();

        return view('admin.videos.create', compact('categories'));
    }

    /**
     * Store a newly created promotional video.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video' => ['required', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('promotional_videos/thumbnails', 'public');
        } elseif ($request->filled('thumbnail_url')) {
            $thumbnailPath = $request->input('thumbnail_url');
        }

        $video = PromotionalVideo::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video' => $validated['video'],
            'thumbnail' => $thumbnailPath,
            'service_category_id' => $validated['service_category_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.videos.index')
            ->with('success', "Promotional Video '{$video->title}' created successfully.");
    }

    /**
     * Show form to edit an existing promotional video.
     */
    public function edit(int $id): View
    {
        $video = PromotionalVideo::findOrFail($id);
        $categories = ServiceCategory::parents()->get();

        return view('admin.videos.edit', compact('video', 'categories'));
    }

    /**
     * Update the specified promotional video in database.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $video = PromotionalVideo::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video' => ['required', 'string', 'max:500'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'service_category_id' => ['nullable', 'exists:service_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $thumbnailPath = $video->thumbnail;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('promotional_videos/thumbnails', 'public');
        } elseif ($request->filled('thumbnail_url')) {
            $thumbnailPath = $request->input('thumbnail_url');
        }

        $video->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video' => $validated['video'],
            'thumbnail' => $thumbnailPath,
            'service_category_id' => $validated['service_category_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.videos.index')
            ->with('success', "Promotional Video '{$video->title}' updated successfully.");
    }

    /**
     * Delete specified promotional video.
     */
    public function destroy(int $id): RedirectResponse
    {
        $video = PromotionalVideo::findOrFail($id);
        $title = $video->title;
        $video->delete();

        return redirect()->route('admin.videos.index')
            ->with('success', "Promotional Video '{$title}' deleted successfully.");
    }

    /**
     * Toggle active status of a video.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $video = PromotionalVideo::findOrFail($id);
        $video->update(['is_active' => ! $video->is_active]);

        $statusStr = $video->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Promotional Video '{$video->title}' has been {$statusStr}.");
    }
}
