<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_id.required' => 'Booking ID wajib diisi',
            'booking_id.exists' => 'Booking tidak ditemukan',
            'rating.required' => 'Rating wajib diisi',
            'rating.between' => 'Rating harus antara 1 sampai 5',
            'comment.required' => 'Komentar wajib diisi',
            'comment.min' => 'Komentar minimal 10 karakter',
            'comment.max' => 'Komentar maksimal 1000 karakter',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}