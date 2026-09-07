<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('property_id', 'idx_images_property');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};