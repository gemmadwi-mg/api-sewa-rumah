<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * List all bookings for owner's properties
     */
    public function index(Request $request): JsonResponse
    {
        $ownerId = $request->user()->id;

        $query = Booking::whereHas('property', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })
            ->with([
                'property:id,title,address,city,price_per_month',
                'tenant:id,name,email,phone',
            ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
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
     * Show booking detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with([
            'property',
            'property.images',
            'tenant:id,name,email,phone',
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check if property belongs to owner
        if ($booking->property->owner_id !== $request->user()->id) {
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
     * Approve pending booking
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with('property')->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($booking->property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini',
            ], 403);
        }

        // Check status
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking dengan status "' . $booking->status_label . '" tidak dapat disetujui',
            ], 422);
        }

        // Use transaction to prevent race condition
        try {
            DB::beginTransaction();

            // Re-check availability inside transaction
            $overlapping = Booking::where('property_id', $booking->property_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'approved')
                ->overlapping($booking->start_date, $booking->end_date)
                ->lockForUpdate()
                ->exists();

            if ($overlapping) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menyetujui booking karena tanggal sudah tidak tersedia',
                ], 422);
            }

            $booking->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyetujui booking',
            ], 500);
        }

        $booking->load('tenant:id,name,email,phone');

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil disetujui',
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Reject pending booking
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter',
            'rejection_reason.max' => 'Alasan penolakan maksimal 500 karakter',
        ]);

        $booking = Booking::with('property')->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
            ], 404);
        }

        // Check ownership
        if ($booking->property->owner_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini',
            ], 403);
        }

        // Check status
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking dengan status "' . $booking->status_label . '" tidak dapat ditolak',
            ], 422);
        }

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil ditolak',
            'data' => new BookingResource($booking),
        ]);
    }
}
