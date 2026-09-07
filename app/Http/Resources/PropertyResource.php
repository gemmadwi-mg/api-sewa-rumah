<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'price_per_month' => (float) $this->price_per_month,
            'formatted_price' => $this->formatted_price,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'facilities' => $this->facilities ?? [],
            'rules' => $this->rules ?? [],
            'status' => $this->status,
            'is_verified' => $this->is_verified,

            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'is_primary' => $img->is_primary,
                    'order' => $img->order,
                ]);
            }),
            'primary_image' => $this->primary_image,

            // Owner
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                ];
            }),

            // Stats
            'average_rating' => $this->when(
                isset($this->reviews_avg_rating),
                fn() => round((float) $this->reviews_avg_rating, 1),
                fn() => $this->average_rating
            ),
            'total_reviews' => $this->when(
                $this->reviews_count !== null,
                $this->reviews_count,
                $this->total_reviews
            ),
            'bookings_count' => $this->whenCounted('bookings'),
            'reviews_count' => $this->whenCounted('reviews'),

            // Relations (when loaded)
            'bookings' => $this->whenLoaded('bookings', function () {
                return BookingResource::collection($this->bookings);
            }),
            // 'reviews' => $this->whenLoaded('reviews', function () {
            //     return ReviewResource::collection($this->reviews);
            // }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
