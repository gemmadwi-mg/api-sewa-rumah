<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PublicPropertyController;
use App\Http\Controllers\Api\V1\Owner\BookingController as OwnerBookingController;
use App\Http\Controllers\Api\V1\Owner\PropertyController as OwnerPropertyController;
use App\Http\Controllers\Api\V1\Tenant\BookingController as TenantBookingController;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Auth routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    // Public property routes (no auth required)
    Route::prefix('properties')->name('properties.')->group(function () {
        Route::get('/', [PublicPropertyController::class, 'index'])->name('index');
        Route::get('/search', [PublicPropertyController::class, 'search'])->name('search');
        Route::get('/cities', [PublicPropertyController::class, 'cities'])->name('cities');
        Route::get('/{id}', [PublicPropertyController::class, 'show'])->name('show');
    });

    // Di dalam group owner
    Route::prefix('owner')->name('owner.')->middleware(['auth:sanctum', 'role:owner'])->group(function () {
        // Properties (existing)
        Route::apiResource('properties', OwnerPropertyController::class);
        Route::post('properties/{property}/images', [OwnerPropertyController::class, 'uploadImages'])
            ->name('properties.images');

        // Bookings
        Route::get('bookings', [OwnerBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{id}', [OwnerBookingController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{id}/approve', [OwnerBookingController::class, 'approve'])->name('bookings.approve');
        Route::put('bookings/{id}/reject', [OwnerBookingController::class, 'reject'])->name('bookings.reject');
    });

    // Tenant routes
    Route::prefix('tenant')->name('tenant.')->middleware(['auth:sanctum', 'role:tenant'])->group(function () {
        // Bookings
        Route::get('bookings', [TenantBookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [TenantBookingController::class, 'store'])->name('bookings.store');
        Route::get('bookings/{id}', [TenantBookingController::class, 'show'])->name('bookings.show');
        Route::put('bookings/{id}/cancel', [TenantBookingController::class, 'cancel'])->name('bookings.cancel');
    });
});
