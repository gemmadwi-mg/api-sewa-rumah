<?php

namespace App\Http\Requests\Api\V1\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'price_per_month' => ['required', 'numeric', 'min:100000'],
            'bedrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:20'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:100'],
            'rules' => ['nullable', 'array'],
            'rules.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul properti wajib diisi',
            'description.required' => 'Deskripsi wajib diisi',
            'description.min' => 'Deskripsi minimal 50 karakter',
            'address.required' => 'Alamat wajib diisi',
            'city.required' => 'Kota wajib diisi',
            'province.required' => 'Provinsi wajib diisi',
            'price_per_month.required' => 'Harga sewa wajib diisi',
            'price_per_month.min' => 'Harga sewa minimal Rp 100.000',
            'bedrooms.required' => 'Jumlah kamar tidur wajib diisi',
            'bedrooms.min' => 'Minimal 1 kamar tidur',
            'bathrooms.required' => 'Jumlah kamar mandi wajib diisi',
            'bathrooms.min' => 'Minimal 1 kamar mandi',
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