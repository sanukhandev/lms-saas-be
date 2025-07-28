<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run available seeders
        $this->call([
            DemoTenantSeeder::class,
            TenantThemeConfigSeeder::class,
            DashboardContentSeeder::class,
            CourseTreeStructureSeeder::class,
        ]);

        // Create additional test user if needed
    }
}
