<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Property info
            'property' => $this->whenLoaded('property', function () {
                return [
                    'id' => $this->property->id,
                    'title' => $this->property->title,
                    'address' => $this->property->address,
                    'city' => $this->property->city,
                    'price_per_month' => (float) $this->property->price_per_month,
                    'formatted_price' => $this->property->formatted_price,
                    'primary_image' => $this->property->primary_image,
                    'owner' => $this->when(
                        $this->property->relationLoaded('owner'),
                        fn() => [
                            'id' => $this->property->owner->id,
                            'name' => $this->property->owner->name,
                            'phone' => $this->when(
                                $this->status === 'approved',
                                $this->property->owner->phone
                            ),
                        ]
                    ),
                ];
            }),

            // Tenant info (for owner view)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'phone' => $this->tenant->phone,
                    'email' => $this->tenant->email,
                ];
            }),

            // Booking details
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'total_months' => $this->total_months,
            'total_price' => (float) $this->total_price,
            'formatted_total_price' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            
            // Status
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,
            'rejection_reason' => $this->when(
                $this->status === 'rejected',
                $this->rejection_reason
            ),

            // Action flags
            'can_cancel' => $this->can_cancel,
            'can_review' => $this->can_review,

            // Review (if exists)
            'review' => $this->whenLoaded('review', function () {
                return [
                    'id' => $this->review->id,
                    'rating' => $this->review->rating,
                    'comment' => $this->review->comment,
                    'created_at' => $this->review->created_at->toISOString(),
                ];
            }),

            // Timestamps
            'approved_at' => $this->approved_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}