<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerAddressResource;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    /**
     * Display a listing of customer's saved addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = CustomerAddress::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Customer addresses retrieved successfully.',
            'data' => CustomerAddressResource::collection($addresses),
        ]);
    }

    /**
     * Store a newly created customer address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'house_no' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:50'],
            'address_line' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()->id;
        $isDefault = $validated['is_default'] ?? false;

        // If this is the user's first address, make it default automatically
        $existingCount = CustomerAddress::where('user_id', $userId)->count();
        if ($existingCount === 0) {
            $isDefault = true;
        }

        if ($isDefault) {
            CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        $addressLine = $validated['address_line'] ?? null;
        if (empty($addressLine)) {
            $parts = array_filter([
                $validated['house_no'] ?? null,
                $validated['street_address'] ?? null,
                $validated['city'] ?? null,
                $validated['pincode'] ?? null,
            ]);
            $addressLine = ! empty($parts) ? implode(', ', $parts) : 'Default Address';
        }

        $address = CustomerAddress::create([
            'user_id' => $userId,
            'label' => $validated['label'] ?? 'Home',
            'house_no' => $validated['house_no'] ?? null,
            'street_address' => $validated['street_address'] ?? null,
            'city' => $validated['city'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'address_line' => $addressLine,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => new CustomerAddressResource($address),
        ], 201);
    }

    /**
     * Update the specified customer address.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $address = CustomerAddress::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'house_no' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:50'],
            'address_line' => ['nullable', 'string'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['is_default']) && $validated['is_default']) {
            CustomerAddress::where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => new CustomerAddressResource($address->fresh()),
        ]);
    }

    /**
     * Remove the specified customer address.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = CustomerAddress::where('user_id', $request->user()->id)->findOrFail($id);
        $wasDefault = $address->is_default;

        $address->delete();

        // If the deleted address was default, promote another address if available
        if ($wasDefault) {
            $nextAddress = CustomerAddress::where('user_id', $request->user()->id)->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    /**
     * Set a specific address as default.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = CustomerAddress::where('user_id', $request->user()->id)->findOrFail($id);

        CustomerAddress::where('user_id', $request->user()->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default address set successfully.',
            'data' => new CustomerAddressResource($address->fresh()),
        ]);
    }
}
