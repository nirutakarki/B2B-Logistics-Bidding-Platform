<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'platform_admin']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@logibid.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('admin123'), 
                'business_id' => null, 
            ]
        );

        $admin->assignRole('platform_admin');

        $this->command->info('Admin user created: admin@logibid.com / admin123');
    }
}
