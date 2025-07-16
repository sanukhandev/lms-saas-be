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
        // Clear existing data (optional - comment out if you want to preserve existing data)
        $this->command->info('Clearing existing data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
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
        
        // Create Super Admin
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
            'tenant_id' => null,
            'email_verified_at' => now(),
        ]);

        // Create Tenant Admin
        $tenantAdmin = User::factory()->create([
            'name' => 'Tenant Admin',
            'email' => 'admin@demo.com',
            'role' => 'admin',
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Create Staff users
        $staff = User::factory()->count(3)->create([
            'role' => 'staff',
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Create Tutors/Instructors
        $tutors = User::factory()->count(8)->create([
            'role' => 'tutor',
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Create Students
        $students = User::factory()->count(50)->create([
            'role' => 'student',
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Creating categories...');
        
        // Create main categories
        $categories = Category::factory()->count(10)->create([
            'tenant_id' => $tenant->id,
        ]);

        // Create subcategories
        $subcategories = [];
        foreach ($categories->take(5) as $category) {
            $subs = Category::factory()->count(rand(2, 4))->create([
                'tenant_id' => $tenant->id,
                'parent_id' => $category->id,
            ]);
            $subcategories = array_merge($subcategories, $subs->toArray());
        }

        $this->command->info('Creating courses...');
        
        // Create courses
        $courses = Course::factory()->count(25)->create([
            'tenant_id' => $tenant->id,
            'category_id' => function () use ($categories, $subcategories) {
                $allCategories = array_merge($categories->toArray(), $subcategories);
                return $allCategories[array_rand($allCategories)]['id'];
            },
        ]);

        $this->command->info('Creating course content...');
        
        // Create course content
        foreach ($courses as $course) {
            CourseContent::factory()->count(rand(5, 15))->create([
                'course_id' => $course->id,
            ]);
        }

        $this->command->info('Creating class sessions...');
        
        // Create class sessions
        foreach ($courses as $course) {
            ClassSession::factory()->count(rand(3, 8))->create([
                'course_id' => $course->id,
                'instructor_id' => $tutors->random()->id,
            ]);
        }

        $this->command->info('Creating exams...');
        
        // Create exams
        $exams = [];
        foreach ($courses as $course) {
            $courseExams = Exam::factory()->count(rand(2, 5))->create([
                'course_id' => $course->id,
                'created_by' => $tutors->random()->id,
            ]);
            $exams = array_merge($exams, $courseExams->toArray());
        }

        $this->command->info('Creating exam questions...');
        
        // Create exam questions
        foreach ($exams as $exam) {
            ExamQuestion::factory()->count(rand(5, 20))->create([
                'exam_id' => $exam['id'],
            ]);
        }

        $this->command->info('Creating exam results...');
        
        // Create exam results
        foreach ($exams as $exam) {
            $examStudents = $students->random(rand(5, 20));
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

            // Assign students to courses
            $courseStudents = $students->random(rand(10, 30));
            foreach ($courseStudents as $student) {
                $course->users()->attach($student->id, ['role' => 'student']);
            }
        }

        $this->command->info('Creating student progress...');
        
        // Create student progress
        foreach ($courses as $course) {
            $courseStudents = $course->users()->where('role', 'student')->get();
            $courseContent = $course->contents;
            
            foreach ($courseStudents as $student) {
                foreach ($courseContent as $content) {
                    StudentProgress::factory()->create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'content_id' => $content->id,
                    ]);
                }
            }
        }

        $this->command->info('Creating notifications...');
        
        // Create notifications for all users
        $allUsers = User::where('tenant_id', $tenant->id)->get();
        foreach ($allUsers as $user) {
            Notification::factory()->count(rand(3, 10))->create([
                'user_id' => $user->id,
            ]);
        }

        // Create some global notifications
        Notification::factory()->count(5)->create([
            'user_id' => $tenantAdmin->id,
            'type' => 'system_announcement',
        ]);

        $this->command->info('Creating course purchases...');
        
        // Create course purchases
        foreach ($courses as $course) {
            $purchaseCount = rand(5, 20);
            $courseStudents = $students->random($purchaseCount);
            
            foreach ($courseStudents as $student) {
                CoursePurchase::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        $this->command->info('Creating feedback...');
        
        // Create feedback for courses
        foreach ($courses as $course) {
            $feedbackCount = rand(3, 15);
            $courseStudents = $course->users()->where('role', 'student')->get();
            
            if ($courseStudents->count() > 0) {
                for ($i = 0; $i < $feedbackCount; $i++) {
                    Feedback::factory()->create([
                        'course_id' => $course->id,
                        'student_id' => $courseStudents->random()->id,
                        'responded_by' => $tutors->random()->id,
                    ]);
                }
            }
        }

        $this->command->info('Creating certificates...');
        
        // Create certificates for completed courses
        foreach ($courses as $course) {
            $completedStudents = $course->users()->where('role', 'student')->get()->random(rand(2, 8));
            
            foreach ($completedStudents as $student) {
                Certificate::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'issued_by' => $tutors->random()->id,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
        $this->command->info('- Notifications for all users');
        
        $this->command->info('Demo credentials:');
        $this->command->info('Super Admin: superadmin@example.com / password');
        $this->command->info('Tenant Admin: admin@demo.com / password');
    }
}
