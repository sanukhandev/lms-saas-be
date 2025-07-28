<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourseTreeStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Populate test data using the tree structure for courses
     */
    public function run(): void
    {
        // Clear all existing course data
        $this->command->info('Clearing all existing course data...');
        Course::query()->delete();
        $this->command->info('All course data cleared successfully!');
        
        // Use existing tenant
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->error('No tenants found in the database. Please create at least one tenant first.');
            return;
        }
        
        $this->command->info("Using existing tenant: {$tenant->name} (ID: {$tenant->id})");

        // Use existing user as instructor (get the first admin/instructor user)
        $instructor = User::where('tenant_id', $tenant->id)->first();
        
        if (!$instructor) {
            $this->command->error('No users found for this tenant. Please create at least one user first.');
            return;
        }
        
        $this->command->info("Using existing user as instructor: {$instructor->name} (ID: {$instructor->id})");

        // Get existing categories or create new ones if they don't exist
        $categories = [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'DevOps',
            'Business'
        ];

        $categoryIds = [];
        foreach ($categories as $categoryName) {
            // Look for existing category with similar name
            $category = Category::where('tenant_id', $tenant->id)
                ->where(function($query) use ($categoryName) {
                    $query->where('name', $categoryName)
                          ->orWhere('name', 'like', "%{$categoryName}%");
                })
                ->first();
                
            // If no similar category exists, create a new one
            if (!$category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'tenant_id' => $tenant->id,
                    'description' => "Category for {$categoryName} courses",
                    'slug' => Str::slug($categoryName),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created new category: {$categoryName}");
            } else {
                $this->command->info("Using existing category: {$category->name} (ID: {$category->id})");
            }
            
            $categoryIds[] = $category->id;
        }

        // Create test courses with modules and chapters
        $courses = [
            [
                'title' => 'Full Stack Web Development',
                'description' => 'Learn modern web development with JavaScript, React, Node.js, and MongoDB',
                'category_id' => $categoryIds[0],
                'modules' => [
                    [
                        'title' => 'JavaScript Fundamentals',
                        'description' => 'Core JavaScript concepts and modern ES6+ features',
                        'chapters' => [
                            [
                                'title' => 'Variables and Data Types',
                                'description' => 'Understanding JavaScript variables and primitive data types',
                                'content' => 'JavaScript has several data types: String, Number, Boolean, Undefined, Null, Symbol, and BigInt.',
                                'video_url' => 'https://example.com/videos/js-variables',
                                'duration_minutes' => 15,
                                'learning_objectives' => json_encode(['Understand variable declaration', 'Know the difference between var, let, and const', 'Work with primitive data types'])
                            ],
                            [
                                'title' => 'Functions and Scope',
                                'description' => 'Working with functions and understanding scope in JavaScript',
                                'content' => 'Functions in JavaScript are first-class objects, meaning they can be passed around like any other value.',
                                'video_url' => 'https://example.com/videos/js-functions',
                                'duration_minutes' => 20,
                                'learning_objectives' => json_encode(['Create and use functions', 'Understand function scope', 'Work with closures'])
                            ],
                        ]
                    ],
                    [
                        'title' => 'React Basics',
                        'description' => 'Getting started with React component-based architecture',
                        'chapters' => [
                            [
                                'title' => 'React Components',
                                'description' => 'Understanding React components and JSX',
                                'content' => 'React components are the building blocks of React applications. They are reusable pieces of code that return React elements.',
                                'video_url' => 'https://example.com/videos/react-components',
                                'duration_minutes' => 25,
                                'learning_objectives' => json_encode(['Create functional components', 'Understand JSX syntax', 'Pass props between components'])
                            ],
                            [
                                'title' => 'State and Hooks',
                                'description' => 'Managing state with React Hooks',
                                'content' => 'Hooks are functions that let you "hook into" React state and lifecycle features from function components.',
                                'video_url' => 'https://example.com/videos/react-hooks',
                                'duration_minutes' => 30,
                                'learning_objectives' => json_encode(['Use useState hook', 'Implement useEffect for side effects', 'Create custom hooks'])
                            ],
                        ]
                    ],
                ]
            ],
            [
                'title' => 'Data Science with Python',
                'description' => 'Learn data analysis, visualization, and machine learning with Python',
                'category_id' => $categoryIds[2],
                'modules' => [
                    [
                        'title' => 'Python for Data Science',
                        'description' => 'Essential Python skills for data analysis',
                        'chapters' => [
                            [
                                'title' => 'NumPy Fundamentals',
                                'description' => 'Working with numerical data using NumPy',
                                'content' => 'NumPy is a library for the Python programming language, adding support for large, multi-dimensional arrays and matrices.',
                                'video_url' => 'https://example.com/videos/numpy-basics',
                                'duration_minutes' => 20,
                                'learning_objectives' => json_encode(['Create and manipulate arrays', 'Perform array operations', 'Use NumPy mathematical functions'])
                            ],
                            [
                                'title' => 'Pandas for Data Analysis',
                                'description' => 'Data manipulation and analysis with Pandas',
                                'content' => 'Pandas is a software library written for the Python programming language for data manipulation and analysis.',
                                'video_url' => 'https://example.com/videos/pandas-intro',
                                'duration_minutes' => 25,
                                'learning_objectives' => json_encode(['Work with DataFrames', 'Filter and transform data', 'Aggregate and group data'])
                            ],
                        ]
                    ],
                    [
                        'title' => 'Introduction to Machine Learning',
                        'description' => 'Basic concepts and algorithms in machine learning',
                        'chapters' => [
                            [
                                'title' => 'Supervised Learning',
                                'description' => 'Classification and regression algorithms',
                                'content' => 'Supervised learning is the machine learning task of learning a function that maps an input to an output based on example input-output pairs.',
                                'video_url' => 'https://example.com/videos/supervised-learning',
                                'duration_minutes' => 35,
                                'learning_objectives' => json_encode(['Understand classification vs regression', 'Implement linear regression', 'Build decision trees'])
                            ],
                            [
                                'title' => 'Model Evaluation',
                                'description' => 'Techniques for evaluating machine learning models',
                                'content' => 'Model evaluation is an essential part of the model development process.',
                                'video_url' => 'https://example.com/videos/model-evaluation',
                                'duration_minutes' => 30,
                                'learning_objectives' => json_encode(['Use train-test splits', 'Implement cross-validation', 'Evaluate using metrics'])
                            ],
                        ]
                    ],
                ]
            ],
            [
                'title' => 'Mobile App Development with Flutter',
                'description' => 'Build cross-platform mobile apps with Flutter and Dart',
                'category_id' => $categoryIds[1],
                'modules' => [
                    [
                        'title' => 'Dart Programming',
                        'description' => 'Learn the Dart programming language',
                        'chapters' => [
                            [
                                'title' => 'Dart Syntax and Types',
                                'description' => 'Basic syntax and data types in Dart',
                                'content' => 'Dart is a client-optimized programming language for fast apps on multiple platforms.',
                                'video_url' => 'https://example.com/videos/dart-basics',
                                'duration_minutes' => 20,
                                'learning_objectives' => json_encode(['Write basic Dart code', 'Work with Dart data types', 'Understand null safety'])
                            ],
                            [
                                'title' => 'Object-Oriented Dart',
                                'description' => 'Object-oriented programming with Dart',
                                'content' => 'Dart is an object-oriented language with classes and mixin-based inheritance.',
                                'video_url' => 'https://example.com/videos/dart-oop',
                                'duration_minutes' => 25,
                                'learning_objectives' => json_encode(['Create classes and objects', 'Implement inheritance', 'Use mixins and extensions'])
                            ],
                        ]
                    ],
                    [
                        'title' => 'Flutter UI',
                        'description' => 'Building user interfaces with Flutter',
                        'chapters' => [
                            [
                                'title' => 'Flutter Widgets',
                                'description' => 'Working with Flutter widgets',
                                'content' => 'In Flutter, almost everything is a widget. Widgets are the basic building blocks of a Flutter app\'s user interface.',
                                'video_url' => 'https://example.com/videos/flutter-widgets',
                                'duration_minutes' => 30,
                                'learning_objectives' => json_encode(['Use basic widgets', 'Create layouts', 'Implement material design'])
                            ],
                            [
                                'title' => 'State Management',
                                'description' => 'Managing state in Flutter applications',
                                'content' => 'State management is a critical aspect of app development, especially in Flutter.',
                                'video_url' => 'https://example.com/videos/flutter-state',
                                'duration_minutes' => 35,
                                'learning_objectives' => json_encode(['Use setState', 'Implement Provider', 'Work with Riverpod'])
                            ],
                        ]
                    ],
                ]
            ]
        ];

        // Create the courses with modules and chapters
        foreach ($courses as $courseData) {
            $this->command->info("Creating course: {$courseData['title']}");
            
            // Create the course
            $course = Course::firstOrCreate(
                [
                    'title' => $courseData['title'],
                    'tenant_id' => $tenant->id,
                    'content_type' => 'course'
                ],
                [
                    'description' => $courseData['description'],
                    'category_id' => $courseData['category_id'],
                    'tenant_id' => $tenant->id,
                    'content_type' => 'course',
                    'status' => 'draft',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Create modules for the course
            foreach ($courseData['modules'] as $moduleIndex => $moduleData) {
                $this->command->info("  Creating module: {$moduleData['title']}");
                
                $module = Course::firstOrCreate(
                    [
                        'title' => $moduleData['title'],
                        'parent_id' => $course->id,
                        'content_type' => 'module',
                        'tenant_id' => $tenant->id
                    ],
                    [
                        'description' => $moduleData['description'],
                        'parent_id' => $course->id,
                        'content_type' => 'module',
                        'position' => $moduleIndex,
                        'tenant_id' => $tenant->id,
                        'category_id' => $course->category_id, // Use the parent course's category_id
                        'status' => 'draft',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // Create chapters for the module
                foreach ($moduleData['chapters'] as $chapterIndex => $chapterData) {
                    $this->command->info("    Creating chapter: {$chapterData['title']}");
                    
                    Course::firstOrCreate(
                        [
                            'title' => $chapterData['title'],
                            'parent_id' => $module->id,
                            'content_type' => 'chapter',
                            'tenant_id' => $tenant->id
                        ],
                        [
                            'description' => $chapterData['description'],
                            'parent_id' => $module->id,
                            'content_type' => 'chapter',
                            'position' => $chapterIndex,
                            'content' => $chapterData['content'],
                            'video_url' => $chapterData['video_url'],
                            'duration_minutes' => $chapterData['duration_minutes'],
                            'learning_objectives' => $chapterData['learning_objectives'],
                            'tenant_id' => $tenant->id,
                            'category_id' => $course->category_id, // Use the parent course's category_id
                            'status' => 'draft',
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        $this->command->info('Course tree structure test data created successfully!');
    }
}
