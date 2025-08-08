<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\Tenant;
use App\Models\Category;
use Carbon\Carbon;

class CourseHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data safely
        $this->command->info('Clearing existing courses and related data...');

        // Delete courses (this will cascade delete related records if set up properly)
        Course::query()->delete();

        // Reset auto-increment
        DB::statement('ALTER TABLE courses AUTO_INCREMENT = 1;');

        // Get demo tenant
        $demoTenant = Tenant::where('domain', 'demo')->first();
        if (!$demoTenant) {
            $this->command->error('Demo tenant not found. Please ensure tenant seeder has run.');
            return;
        }

        $this->command->info("Found demo tenant with ID: {$demoTenant->id}");

        // Create or get a default category
        $defaultCategory = Category::firstOrCreate([
            'tenant_id' => $demoTenant->id,
            'name' => 'Technology'
        ], [
            'slug' => 'technology',
            'description' => 'Technology and programming courses',
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info("Using category: {$defaultCategory->name} (ID: {$defaultCategory->id})");
        $this->command->info('Creating course hierarchy structure...');

        // Create sample courses with proper hierarchy
        $this->command->info('Creating Full Stack Course...');
        $this->createFullStackCourse($demoTenant->id, $defaultCategory->id);

        $this->command->info('Course hierarchy seeding completed!');
    }

    private function createFullStackCourse($tenantId, $categoryId)
    {
        // Create main course
        $course = Course::create([
            'tenant_id' => $tenantId,
            'category_id' => $categoryId,
            'title' => 'Full Stack Web Development',
            'description' => 'Complete web development course covering frontend, backend, and deployment',
            'content_type' => 'course',
            'parent_id' => null,
            'position' => 1,
            'status' => 'published',
            'level' => 'intermediate',
            'duration_hours' => 120,
            'price' => 299.99,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info("Created course: {$course->title} with ID: {$course->id}");

        // Module 1: Frontend Development
        $frontendModule = Course::create([
            'tenant_id' => $tenantId,
            'category_id' => $categoryId,
            'title' => 'Frontend Development',
            'description' => 'Learn modern frontend technologies including React, TypeScript, and CSS frameworks',
            'content_type' => 'module',
            'parent_id' => $course->id,
            'position' => 1,
            'duration_hours' => 50,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info("Created module: {$frontendModule->title}");

        // Chapter 1.1: React Fundamentals
        $reactChapter = Course::create([
            'tenant_id' => $tenantId,
            'category_id' => $categoryId,
            'title' => 'React Fundamentals',
            'description' => 'Learn the basics of React including components, props, and state',
            'content_type' => 'chapter',
            'parent_id' => $frontendModule->id,
            'position' => 1,
            'duration_hours' => 20,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info("Created chapter: {$reactChapter->title}");

        // Classes for React Fundamentals
        $reactClasses = [
            ['title' => 'Introduction to React', 'duration' => 90],
            ['title' => 'Components and JSX', 'duration' => 120],
            ['title' => 'Props and State Management', 'duration' => 120],
        ];

        foreach ($reactClasses as $index => $classData) {
            $class = Course::create([
                'tenant_id' => $tenantId,
                'category_id' => $categoryId,
                'title' => $classData['title'],
                'description' => 'Learn ' . strtolower($classData['title']),
                'content_type' => 'class',
                'parent_id' => $reactChapter->id,
                'position' => $index + 1,
                'duration_minutes' => $classData['duration'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $this->command->info("Created class: {$class->title}");
        }
    }
}
