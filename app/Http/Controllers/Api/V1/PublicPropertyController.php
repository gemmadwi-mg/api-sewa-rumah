<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPropertyController extends Controller
{
    /**
     * List all active and verified properties
     */
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->available() // scope: active + verified
            ->with(['images' => fn($q) => $q->where('is_primary', true)])
            ->with(['owner:id,name'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(12);

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
     * Show property detail
     */
    public function show(int $id): JsonResponse
    {
        $property = Property::query()
            ->available()
            ->with(['images', 'owner:id,name'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->find($id);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak ditemukan',
            ], 404);
        }

        // Load reviews separately with pagination
        $reviews = $property->reviews()
            ->with('tenant:id,name')
            ->latest()
            ->paginate(5);

        return response()->json([
            'success' => true,
            'message' => 'Detail properti berhasil diambil',
            'data' => [
                'property' => new PropertyResource($property),
                'reviews' => [
                    'data' => $reviews->items(),
                    'meta' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Search and filter properties
     */
    public function search(Request $request): JsonResponse
    {
        $query = Property::query()->available();

        // Keyword search
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        // City filter
        if ($request->filled('city')) {
            $query->byCity($request->city);
        }

        // Province filter
        if ($request->filled('province')) {
            $query->byProvince($request->province);
        }

        // Price range filter
        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->priceRange(
                $request->min_price ? (float) $request->min_price : null,
                $request->max_price ? (float) $request->max_price : null
            );
        }

        // Bedrooms filter
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', '>=', (int) $request->bedrooms);
        }

        // Bathrooms filter
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', '>=', (int) $request->bathrooms);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'newest');
        $query = $this->applySorting($query, $sortBy);

        // Eager load relations
        $query->with(['images' => fn($q) => $q->where('is_primary', true)])
              ->with(['owner:id,name'])
              ->withCount('reviews')
              ->withAvg('reviews', 'rating');

        // Pagination
        $perPage = min((int) $request->input('per_page', 12), 50);
        $properties = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian properti',
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
            'filters' => [
                'keyword' => $request->keyword,
                'city' => $request->city,
                'province' => $request->province,
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
                'bedrooms' => $request->bedrooms,
                'bathrooms' => $request->bathrooms,
                'sort_by' => $sortBy,
            ],
        ]);
    }

    /**
     * Get available cities for filter dropdown
     */
    public function cities(): JsonResponse
    {
        $cities = Property::query()
            ->available()
            ->select('city', 'province')
            ->selectRaw('COUNT(*) as property_count')
            ->groupBy('city', 'province')
            ->orderBy('property_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kota berhasil diambil',
            'data' => $cities,
        ]);
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, string $sortBy)
    {
        return match ($sortBy) {
            'price_asc' => $query->orderBy('price_per_month', 'asc'),
            'price_desc' => $query->orderBy('price_per_month', 'desc'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            'oldest' => $query->oldest(),
            default => $query->latest(), // newest
        };
    }
}