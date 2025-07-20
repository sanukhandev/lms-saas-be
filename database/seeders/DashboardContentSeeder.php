<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\CoursePurchase;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamResult;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\StudentProgress;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting dashboard content seeding...');
        
        // Get or create demo tenant
        $tenant = Tenant::where('domain', 'demo')->first();
        if (!$tenant) {
            $tenant = Tenant::factory()->create([
                'name' => 'Demo Tenant',
                'domain' => 'demo',
                'slug' => 'demo',
                'status' => 'active',
            ]);
        }

        $this->command->info('Creating users...');
        
        // Create Tenant Admin
        $tenantAdmin = User::where('email', 'admin@demo.com')->first();
        if (!$tenantAdmin) {
            $tenantAdmin = User::factory()->create([
                'name' => 'Tenant Admin',
                'email' => 'admin@demo.com',
                'role' => 'admin',
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);
        }

        // Create Staff Users (reduce from 3 to 2)
        $staff = User::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
            'role' => 'staff',
        ]);

        // Create Tutors (reduce from 8 to 3)
        $tutors = User::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'role' => 'tutor',
        ]);

        // Create Students (reduce from 50 to 15)
        $students = User::factory()->count(15)->create([
            'tenant_id' => $tenant->id,
            'role' => 'student',
        ]);

        $this->command->info('Creating categories...');
        
        // Create Categories (reduce from 10 to 5)
        $existingCategories = Category::where('tenant_id', $tenant->id)->whereNull('parent_id')->count();
        if ($existingCategories < 5) {
            $categories = Category::factory()->count(5 - $existingCategories)->create([
                'tenant_id' => $tenant->id,
                'parent_id' => null,
            ]);
        } else {
            $categories = Category::where('tenant_id', $tenant->id)->whereNull('parent_id')->get();
        }

        // Create subcategories (reduce from 3 to 2 per category)
        $subcategories = [];
        foreach ($categories as $category) {
            $existingSubs = Category::where('tenant_id', $tenant->id)->where('parent_id', $category->id)->count();
            if ($existingSubs < 2) {
                $subs = Category::factory()->count(2 - $existingSubs)->create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $category->id,
                ]);
                $subcategories = array_merge($subcategories, $subs->toArray());
            }
        }

        $this->command->info('Creating courses...');
        
        // Create courses (reduce from 25 to 8)
        $courses = Course::factory()->count(8)->create([
            'tenant_id' => $tenant->id,
            'category_id' => function () use ($categories, $subcategories) {
                $allCategories = array_merge($categories->toArray(), $subcategories);
                return $allCategories[array_rand($allCategories)]['id'];
            },
        ]);

        $this->command->info('Creating course content...');
        
        // Create course content (reduce from 5-15 to 3-6)
        foreach ($courses as $course) {
            CourseContent::factory()->count(rand(3, 6))->create([
                'course_id' => $course->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->command->info('Creating class sessions...');
        
        // Create class sessions (reduce from 3-8 to 2-4)
        foreach ($courses as $course) {
            ClassSession::factory()->count(rand(2, 4))->create([
                'course_id' => $course->id,
                'tutor_id' => $tutors->random()->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->command->info('Creating exams...');
        
        // Create exams (reduce from 2-5 to 1-2 per course)
        $exams = [];
        foreach ($courses as $course) {
            $courseExams = Exam::factory()->count(rand(1, 2))->create([
                'course_id' => $course->id,
                'tenant_id' => $tenant->id,
            ]);
            $exams = array_merge($exams, $courseExams->toArray());
        }

        $this->command->info('Creating exam questions...');
        
        // Create exam questions (reduce from 5-20 to 3-8 per exam)
        foreach ($exams as $exam) {
            ExamQuestion::factory()->count(rand(3, 8))->create([
                'exam_id' => $exam['id'],
            ]);
        }

        $this->command->info('Creating exam results...');
        
        // Create exam results (reduce from 5-20 to 2-8 to fit available students)
        foreach ($exams as $exam) {
            $examStudents = $students->random(rand(2, min(8, $students->count())));
            foreach ($examStudents as $student) {
                ExamResult::factory()->create([
                    'exam_id' => $exam['id'],
                    'student_id' => $student->id,
                ]);
            }
        }

        $this->command->info('Creating course enrollments...');
        
        // Create course enrollments (many-to-many relationships)
        foreach ($courses as $course) {
            // Assign instructors to courses
            $courseInstructors = $tutors->random(rand(1, 3));
            foreach ($courseInstructors as $instructor) {
                $course->users()->attach($instructor->id, ['role' => 'instructor']);
            }

            // Assign students to courses (reduce from 10-30 to 3-8 to fit available students)
            $courseStudents = $students->random(rand(3, min(8, $students->count())));
            foreach ($courseStudents as $student) {
                $course->users()->attach($student->id, ['role' => 'student']);
            }
        }

        $this->command->info('Creating student progress...');
        
        // Create student progress
        foreach ($courses as $course) {
            $courseStudents = $course->users()->wherePivot('role', 'student')->get();
            $courseContent = $course->contents;
            
            foreach ($courseStudents as $student) {
                foreach ($courseContent as $content) {
                    StudentProgress::factory()->create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'content_id' => $content->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }
        }

        // $this->command->info('Creating notifications...');
        
        // // Create notifications for all users
        // $allUsers = User::where('tenant_id', $tenant->id)->get();
        // foreach ($allUsers as $user) {
        //     Notification::factory()->count(rand(3, 10))->create([
        //         'user_id' => $user->id,
        //         'tenant_id' => $tenant->id,
        //     ]);
        // }

        // // Create some global notifications
        // Notification::factory()->count(5)->create([
        //     'user_id' => $tenantAdmin->id,
        //     'tenant_id' => $tenant->id,
        // ]);

        $this->command->info('Creating course purchases...');
        
        // Create course purchases (reduce from 5-20 to 2-5 per course)
        foreach ($courses as $course) {
            $purchaseCount = rand(2, 5);
            $courseStudents = $students->random($purchaseCount);
            
            foreach ($courseStudents as $student) {
                CoursePurchase::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'tenant_id' => $tenant->id,
                ]);
            }
        }

        $this->command->info('Creating feedback...');
        
        // Create feedback for courses (reduce from 3-15 to 1-3 per course)
        foreach ($courses as $course) {
            $feedbackCount = rand(1, 3);
            $courseStudents = $course->users()->wherePivot('role', 'student')->get();
            
            if ($courseStudents->count() > 0) {
                for ($i = 0; $i < $feedbackCount; $i++) {
                    Feedback::factory()->create([
                        'course_id' => $course->id,
                        'student_id' => $courseStudents->random()->id,
                        'tutor_id' => $tutors->random()->id,
                        'tenant_id' => $tenant->id,
                    ]);
                }
            }
        }

        $this->command->info('Creating certificates...');
        
        // Create certificates for completed courses (reduce from 2-8 to 1-3 per course)
        foreach ($courses as $course) {
            $completedStudents = $course->users()->wherePivot('role', 'student')->get()->random(rand(1, 3));
            
            foreach ($completedStudents as $student) {
                Certificate::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'tenant_id' => $tenant->id,
                ]);
            }
        }

        $this->command->info('Dashboard content seeding completed!');
        $this->command->info('Created:');
        $this->command->info('- 1 Super Admin');
        $this->command->info('- 1 Tenant Admin');
        $this->command->info('- 3 Staff members');
        $this->command->info('- 8 Tutors/Instructors');
        $this->command->info('- 50 Students');
        $this->command->info('- ' . count($categories) . ' Categories');
        $this->command->info('- ' . count($subcategories) . ' Subcategories');
        $this->command->info('- ' . count($courses) . ' Courses');
        $this->command->info('- Course contents, sessions, exams, and progress data');
        $this->command->info('- Course purchases, feedback, and certificates');
        
        $this->command->info('Demo credentials:');
        $this->command->info('Super Admin: superadmin@example.com / password');
        $this->command->info('Tenant Admin: admin@demo.com / password');
    }
}
