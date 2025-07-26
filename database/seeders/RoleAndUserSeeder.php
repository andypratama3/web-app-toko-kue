<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cache roles & permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Roles
        $adminRole = Role::create(['name' => 'admin']);
        $kurirRole = Role::create(['name' => 'kurir']);

        // Buat User Admin Surabaya
        User::create([
            'name' => 'Admin Surabaya',
            'email' => 'admin.surabaya@example.com',
            'region' => 'Surabaya',
            'password' => bcrypt('password')
        ])->assignRole($adminRole);

        // Buat User Admin Malang
        User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.malang@example.com',
            'region' => 'Malang',
            'password' => bcrypt('password')
        ])->assignRole($adminRole);

        // Buat User Admin Denpasar
        User::create([
            'name' => 'Admin Denpasar',
            'email' => 'admin.denpasar@example.com',
            'region' => 'Denpasar',
            'password' => bcrypt('password')
        ])->assignRole($adminRole);

        // Buat User Kurir Surabaya
        User::create([
            'name' => 'Kurir Surabaya',
            'email' => 'kurir.surabaya@example.com',
            'region' => 'Surabaya',
            'password' => bcrypt('password')
        ])->assignRole($kurirRole);

        // Buat User Kurir Malang
        User::create([
            'name' => 'Kurir Malang',
            'email' => 'kurir.malang@example.com',
            'region' => 'Malang',
            'password' => bcrypt('password')
        ])->assignRole($kurirRole);

        // Buat User Kurir Denpasar
        User::create([
            'name' => 'Kurir Denpasar',
            'email' => 'kurir.denpasar@example.com',
            'region' => 'Denpasar',
            'password' => bcrypt('password')
        ])->assignRole($kurirRole);

        
    }
}
