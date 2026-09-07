<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'price_per_month',
        'bedrooms',
        'bathrooms',
        'facilities',
        'rules',
        'status',
        'is_verified',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price_per_month' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'facilities' => 'array',
        'rules' => 'array',
        'is_verified' => 'boolean',
    ];

    // Relationships

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->verified();
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByProvince($query, string $province)
    {
        return $query->where('province', $province);
    }

    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('price_per_month', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price_per_month', '<=', $max);
        }
        return $query;
    }

    public function scopeMinBedrooms($query, int $bedrooms)
    {
        return $query->where('bedrooms', '>=', $bedrooms);
    }

    public function scopeMinBathrooms($query, int $bathrooms)
    {
        return $query->where('bathrooms', '>=', $bathrooms);
    }

    // Accessors

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price_per_month, 0, ',', '.');
    }

    public function getPrimaryImageAttribute(): ?string
    {
        return $this->images->firstWhere('is_primary', true)?->image_url
            ?? $this->images->first()?->image_url;
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function getTotalReviewsAttribute(): int
    {
        return $this->reviews()->count();
    }
}