<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;

class DummyPropertySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'owner1@sewarumah.com')->first();

        if (!$owner) {
            $this->command->warn('Owner not found. Run AdminUserSeeder first.');
            return;
        }

        $properties = [
            [
                'title' => 'Rumah Minimalis di Menteng',
                'description' => 'Rumah minimalis modern dengan 3 kamar tidur, cocok untuk keluarga kecil. Lokasi strategis dekat dengan pusat kota.',
                'address' => 'Jl. Menteng Raya No. 45',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'postal_code' => '10310',
                'price_per_month' => 15000000,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'facilities' => ['AC', 'WiFi', 'Parkir', 'Taman'],
                'rules' => ['Tidak boleh pelihara hewan', 'Jam malam 22:00'],
                'status' => 'active',
                'is_verified' => true,
            ],
            [
                'title' => 'Apartemen Studio di BSD',
                'description' => 'Apartemen studio fully furnished, cocok untuk single atau pasangan muda.',
                'address' => 'BSD Green Office Park Tower A',
                'city' => 'Tangerang Selatan',
                'province' => 'Banten',
                'postal_code' => '15345',
                'price_per_month' => 4500000,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'facilities' => ['AC', 'WiFi', 'Gym', 'Pool', 'Security 24 Jam'],
                'rules' => ['Tidak boleh merokok di dalam unit'],
                'status' => 'active',
                'is_verified' => true,
            ],
            [
                'title' => 'Kos Eksklusif di Bandung',
                'description' => 'Kos dengan fasilitas lengkap di kawasan Dago, dekat kampus ITB.',
                'address' => 'Jl. Dago No. 123',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40135',
                'price_per_month' => 2500000,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'facilities' => ['AC', 'WiFi', 'Laundry', 'Dapur Bersama'],
                'rules' => ['Khusus mahasiswa', 'Tidak boleh bawa tamu menginap'],
                'status' => 'active',
                'is_verified' => false,
            ],
        ];

        foreach ($properties as $data) {
            Property::firstOrCreate(
                ['title' => $data['title'], 'owner_id' => $owner->id],
                array_merge($data, ['owner_id' => $owner->id])
            );
        }

        $this->command->info('Dummy properties created: ' . count($properties));
    }
}