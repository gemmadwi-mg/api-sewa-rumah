<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * List reviews for a property
     */
    public function index(Request $request, int $propertyId): JsonResponse
    {
        $reviews = Review::where('property_id', $propertyId)
            ->with('tenant:id,name')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar review berhasil diambil',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Create new review
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Get booking
        $booking = Booking::find($validated['booking_id']);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($booking->tenant_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini',
            ], 403);
        }

        // Check booking status
        if ($booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Review hanya bisa dibuat setelah masa sewa selesai',
            ], 422);
        }

        // Check if already reviewed
        if ($booking->review) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan review untuk booking ini',
            ], 422);
        }

        // Create review
        $review = Review::create([
            'booking_id' => $booking->id,
            'property_id' => $booking->property_id,
            'tenant_id' => $user->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        $review->load(['tenant:id,name', 'property:id,title']);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dibuat. Terima kasih atas feedback Anda!',
            'data' => new ReviewResource($review),
        ], 201);
    }
}