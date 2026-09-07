<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                  ->unique()
                  ->constrained('bookings')
                  ->cascadeOnDelete();
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();
            $table->foreignId('tenant_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->text('owner_response')->nullable();
            $table->timestamps();

            $table->index('property_id', 'idx_reviews_property');
            $table->index('tenant_id', 'idx_reviews_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};