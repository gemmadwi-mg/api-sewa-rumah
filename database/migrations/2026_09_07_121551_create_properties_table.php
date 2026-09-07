<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('price_per_month', 12, 2);
            $table->unsignedTinyInteger('bedrooms');
            $table->unsignedTinyInteger('bathrooms');
            $table->json('facilities')->nullable();
            $table->json('rules')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id', 'idx_properties_owner');
            $table->index('city', 'idx_properties_city');
            $table->index(['status', 'is_verified'], 'idx_properties_status');
            $table->index('price_per_month', 'idx_properties_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};