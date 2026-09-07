<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tenant\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * List tenant's bookings
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::where('tenant_id', $request->user()->id)
            ->with(['property:id,title,address,city,price_per_month,status', 'property.images' => fn($q) => $q->where('is_primary', true)]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Create new booking
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Get property
        $property = Property::available()->find($validated['property_id']);

        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak tersedia atau tidak ditemukan',
            ], 404);
        }

        // Check for existing active booking by same tenant
        $existingBooking = Booking::where('property_id', $property->id)
            ->where('tenant_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki booking aktif untuk properti ini',
            ], 422);
        }

        // Check availability (no overlap with approved bookings)
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $overlappingBooking = Booking::where('property_id', $property->id)
            ->where('status', 'approved')
            ->overlapping($startDate, $endDate)
            ->exists();

        if ($overlappingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Properti tidak tersedia pada tanggal yang dipilih. Silakan pilih tanggal lain.',
            ], 422);
        }

        // Calculate total months and price
        $totalMonths = $startDate->diffInMonths($endDate);
        if ($totalMonths < 1) {
            $totalMonths = 1; // Minimum 1 month
        }
        $totalPrice = $property->price_per_month * $totalMonths;

        // Create booking
        $booking = Booking::create([
            'property_id' => $property->id,
            'tenant_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_months' => $totalMonths,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        $booking->load(['property', 'property.images', 'property.owner:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat. Menunggu persetujuan owner.',
            'data' => new BookingResource($booking),
        ], 201);
    }

    /**
     * Show booking detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with([
            'property',
            'property.images',
            'property.owner:id,name,phone',
            'review',
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($booking->tenant_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail booking berhasil diambil',
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($booking->tenant_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini',
            ], 403);
        }

        // Check if can cancel
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking dengan status "' . $booking->status_label . '" tidak dapat dibatalkan',
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $booking->load('property');

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibatalkan',
            'data' => new BookingResource($booking),
        ]);
    }
}