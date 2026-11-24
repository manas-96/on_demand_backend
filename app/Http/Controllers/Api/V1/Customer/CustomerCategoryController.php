<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\PromotionalVideoResource;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\Banner;
use App\Models\PromotionalVideo;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCategoryController extends Controller
{
    /**
     * Get parent service categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceCategory::parents()
            ->where('is_active', true)
            ->withCount('subcategories');

        if ($search = $request->query('search')) {
            $search = strtolower(trim($search));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%")
                    ->orWhereHas('subcategories', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('tags', 'like', "%{$search}%");
                    });
            });
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Parent categories retrieved successfully.',
            'data' => ServiceCategoryResource::collection($categories),
        ]);
    }

    /**
     * Get single category or subcategory details by ID.
     */
    public function show(int $id): JsonResponse
    {
        $category = ServiceCategory::with(['parent', 'subcategories'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Category details retrieved successfully.',
            'data' => new ServiceCategoryResource($category),
        ]);
    }

    /**
     * Get subcategories for a given parent category ID.
     */
    public function subcategories(Request $request, int $id): JsonResponse
    {
        $parent = ServiceCategory::whereNull('parent_id')->findOrFail($id);

        $query = ServiceCategory::onlySubcategories()
            ->where('parent_id', $parent->id)
            ->where('is_active', true);

        if ($search = $request->query('search')) {
            $search = strtolower(trim($search));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        $subcategories = $query->get();

        return response()->json([
            'success' => true,
            'message' => "Subcategories for category '{$parent->name}' retrieved successfully.",
            'data' => ServiceCategoryResource::collection($subcategories),
        ]);
    }

    /**
     * Get active promotional banners.
     */
    public function banners(): JsonResponse
    {
        $banners = Banner::active()
            ->with(['category', 'subCategory'])
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active banners retrieved successfully.',
            'data' => BannerResource::collection($banners),
        ]);
    }

    /**
     * Get active promotional videos.
     */
    public function videos(): JsonResponse
    {
        $videos = PromotionalVideo::active()
            ->with('category')
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active promotional videos retrieved successfully.',
            'data' => PromotionalVideoResource::collection($videos),
        ]);
    }
}
