<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTestingSeeder extends Seeder
{
    /**
     * Kosongkan tabel user dan buat akun Super Admin baru dengan akses tertinggi.
     */
    public function run(): void
    {
        // 1. Bersihkan seluruh user lama
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Buat akun Super Admin baru (Level PB / Pengurus Besar dengan akses nasional penuh)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'pb', // Role hirarki tertinggi pada sistem (Nasional / Global Access)
            'roles' => ['pb', 'super_admin'],
            'is_active' => true,
            'email_verified_at' => now(),
            'province_id' => null,
            'city_id' => null,
            'dojo_id' => null,
            'belt_level_id' => null,
            'whatsapp' => '081234567890',
        ]);
    }
}
