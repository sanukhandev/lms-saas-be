<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * The seeders are called in a specific order to ensure that dependencies are met:
     * 1. DemoTenantSeeder - Creates the tenant records
     * 2. TenantThemeConfigSeeder - Adds theme configuration to tenants
     * 3. DashboardContentSeeder - Creates users, categories, courses, and related data
     * 4. CourseTreeStructureSeeder - Creates course tree structures
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding process...');
        
        // 1. Create tenants first
        $this->command->info('Running DemoTenantSeeder...');
        $this->call(DemoTenantSeeder::class);
        
        // 2. Configure tenant themes
        $this->command->info('Running TenantThemeConfigSeeder...');
        $this->call(TenantThemeConfigSeeder::class);
        
        // 3. Create dashboard content (users, categories, courses, etc.)
        $this->command->info('Running DashboardContentSeeder...');
        $this->call(DashboardContentSeeder::class);
        
        // 4. Create course tree structures
        $this->command->info('Running CourseTreeStructureSeeder...');
        $this->call(CourseTreeStructureSeeder::class);
        
        $this->command->info('Database seeding completed successfully!');
    }
}
