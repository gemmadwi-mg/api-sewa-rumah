<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                  ->constrained('properties')
                  ->cascadeOnDelete();
            $table->foreignId('tenant_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('total_months');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', [
                'pending', 
                'approved', 
                'rejected', 
                'cancelled', 
                'completed', 
                'expired'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('property_id', 'idx_bookings_property');
            $table->index('tenant_id', 'idx_bookings_tenant');
            $table->index('status', 'idx_bookings_status');
            $table->index(
                ['property_id', 'start_date', 'end_date', 'status'],
                'idx_bookings_availability'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};