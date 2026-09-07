<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Owner\StorePropertyRequest;
use App\Http\Requests\Api\V1\Owner\UpdatePropertyRequest;
use App\Http\Requests\Api\V1\Owner\UploadPropertyImagesRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    /**
     * List owner's properties
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::where('owner_id', $request->user()->id)
            ->with(['images'])
            ->withCount(['bookings', 'reviews']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $properties = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar properti berhasil diambil',
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    /**
     * Create new property
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check if owner is verified
        if (!$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum diverifikasi. Silakan hubungi admin untuk verifikasi.',
            ], 403);
        }

        $validated = $request->validated();
        $validated['owner_id'] = $user->id;
        $validated['status'] = 'draft'; // Always start as draft

        $property = Property::create($validated);
        $property->load('images');

        return response()->json([
            'success' => true,
            'message' => 'Properti berhasil dibuat',
            'data' => new PropertyResource($property),
        ], 201);
    }

    /**
     * Show property detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $property = Property::with(['images', 'bookings', 'reviews.tenant'])
            ->withCount(['bookings', 'reviews'])
            ->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke properti ini',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail properti berhasil diambil',
            'data' => new PropertyResource($property),
        ]);
    }

    /**
     * Update property
     */
    public function update(UpdatePropertyRequest $request, int $id): JsonResponse
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke properti ini',
            ], 403);
        }

        $property->update($request->validated());
        $property->load('images');

        return response()->json([
            'success' => true,
            'message' => 'Properti berhasil diupdate',
            'data' => new PropertyResource($property),
        ]);
    }

    /**
     * Delete property (soft delete)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke properti ini',
            ], 403);
        }

        // Check for active bookings
        $activeBookings = $property->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($activeBookings) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus properti yang memiliki booking aktif',
            ], 422);
        }

        $property->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Properti berhasil dihapus',
            'data' => null,
        ]);
    }

    /**
     * Upload property images
     */
    public function uploadImages(UploadPropertyImagesRequest $request, int $id): JsonResponse
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke properti ini',
            ], 403);
        }

        // Check max images limit
        $currentCount = $property->images()->count();
        $newCount = count($request->file('images'));
        
        if ($currentCount + $newCount > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 10 gambar per properti. Saat ini: ' . $currentCount,
            ], 422);
        }

        $uploadedImages = [];
        $order = $currentCount;

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('properties/' . $property->id, 'public');
            
            $isPrimary = $currentCount === 0 && $index === 0; // First image is primary if no images exist
            
            $propertyImage = PropertyImage::create([
                'property_id' => $property->id,
                'image_url' => Storage::url($path),
                'is_primary' => $request->input('primary_index') === $index ? true : $isPrimary,
                'order' => $order++,
            ]);

            $uploadedImages[] = $propertyImage;
        }

        // Ensure only one primary image
        if ($request->has('primary_index')) {
            PropertyImage::where('property_id', $property->id)
                ->where('id', '!=', $uploadedImages[$request->input('primary_index')]->id ?? 0)
                ->update(['is_primary' => false]);
        }

        $property->load('images');

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' gambar berhasil diupload',
            'data' => new PropertyResource($property),
        ], 201);
    }
}