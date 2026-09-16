<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(MasterDataSeeder::class);
        $this->call(WorldCitiesSeeder::class);
        $this->call(IndianLocalitiesSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'phone' => '9000000000',
        ]);
        $admin->assignRole('admin');
    }
}
