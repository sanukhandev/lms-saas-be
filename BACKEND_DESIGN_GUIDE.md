# Backend Design & Code Structure Guide

## 📋 Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Multi-Tenant Implementation](#multi-tenant-implementation)
3. [Service Layer Pattern](#service-layer-pattern)
4. [API Controller Structure](#api-controller-structure)
5. [Model Design Patterns](#model-design-patterns)
6. [Database Architecture](#database-architecture)
7. [Authentication & Authorization](#authentication--authorization)
8. [Caching Strategy](#caching-strategy)
9. [Building New Modules](#building-new-modules)
10. [Testing Patterns](#testing-patterns)
11. [Best Practices](#best-practices)

## 🏗️ Architecture Overview

### Layered Architecture
The LMS backend follows a **Service-Oriented Architecture (SOA)** with clear separation of concerns:

```
┌─────────────────────────────────────────┐
│              HTTP Layer                 │
│  Controllers → Middleware → Requests    │
├─────────────────────────────────────────┤
│            Service Layer                │
│     Business Logic & Operations         │
├─────────────────────────────────────────┤
│            Data Layer                   │
│   Models → Repositories → Database      │
├─────────────────────────────────────────┤
│          Infrastructure Layer           │
│   Cache → Queue → Storage → External    │
└─────────────────────────────────────────┘
```

### Directory Structure Patterns
```
app/
├── Http/
│   ├── Controllers/Api/        # API controllers (one per resource)
│   ├── Middleware/            # Custom middleware
│   └── Requests/              # Form request validation
├── Services/                  # Business logic services
│   ├── Auth/                 # Authentication services
│   ├── Course/               # Course management
│   ├── User/                 # User management
│   └── [Module]/             # Feature-specific services
├── Models/                    # Eloquent models
├── DTOs/                     # Data Transfer Objects
├── Traits/                   # Reusable traits
└── Utils/                    # Utility classes
```

## 🏢 Multi-Tenant Implementation

### Tenant Detection Strategy
```php
// app/Http/Middleware/TenantAccessMiddleware.php
class TenantAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $domain = $request->header('X-Tenant-Domain');
        
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
        } elseif ($domain) {
            $tenant = Tenant::where('domain', $domain)->first();
        }
        
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        // Set tenant context globally
        app()->instance('current-tenant', $tenant);
        config(['database.connections.tenant.database' => $tenant->database]);
        
        return $next($request);
    }
}
```

### Tenant-Aware Models
```php
// app/Traits/TenantAware.php
trait TenantAware
{
    protected static function bootTenantAware()
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (app()->has('current-tenant')) {
                $model->tenant_id = app('current-tenant')->id;
            }
        });
    }
}

// app/Models/Course.php
class Course extends Model
{
    use TenantAware;
    
    protected $fillable = [
        'title', 'description', 'price', 'category_id', 'tenant_id'
    ];
}
```

### Tenant Scope Implementation
```php
// app/Models/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->has('current-tenant')) {
            $builder->where('tenant_id', app('current-tenant')->id);
        }
    }
    
    public function extend(Builder $builder)
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
```

## 🔧 Service Layer Pattern

### Service Structure Template
```php
// app/Services/[Module]/[Module]Service.php
namespace App\Services\Course;

use App\DTOs\Course\CreateCourseDTO;
use App\DTOs\Course\UpdateCourseDTO;
use App\Models\Course;
use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService extends BaseService
{
    public function __construct(
        private Course $courseModel
    ) {}
    
    public function getAllCourses(array $filters = []): LengthAwarePaginator
    {
        $query = $this->courseModel->newQuery();
        
        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        return $query->with(['category', 'instructor'])
                    ->paginate($filters['per_page'] ?? 15);
    }
    
    public function createCourse(CreateCourseDTO $dto): Course
    {
        return $this->courseModel->create([
            'title' => $dto->title,
            'description' => $dto->description,
            'price' => $dto->price,
            'category_id' => $dto->categoryId,
            'instructor_id' => $dto->instructorId,
            'status' => 'draft'
        ]);
    }
    
    public function updateCourse(int $id, UpdateCourseDTO $dto): Course
    {
        $course = $this->courseModel->findOrFail($id);
        
        $course->update([
            'title' => $dto->title ?? $course->title,
            'description' => $dto->description ?? $course->description,
            'price' => $dto->price ?? $course->price,
            'category_id' => $dto->categoryId ?? $course->category_id,
        ]);
        
        return $course->fresh();
    }
    
    public function deleteCourse(int $id): bool
    {
        $course = $this->courseModel->findOrFail($id);
        return $course->delete();
    }
    
    public function getCourseWithLessons(int $id): Course
    {
        return $this->courseModel->with(['lessons', 'category', 'instructor'])
                                ->findOrFail($id);
    }
}
```

### Base Service Class
```php
// app/Services/BaseService.php
abstract class BaseService
{
    protected function handleServiceException(\Exception $e, string $operation = 'operation')
    {
        \Log::error("Service {$operation} failed", [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        throw new ServiceException("Failed to perform {$operation}: " . $e->getMessage());
    }
    
    protected function getCacheKey(string $prefix, ...$params): string
    {
        $tenantId = app('current-tenant')->id ?? 'global';
        return "{$prefix}:{$tenantId}:" . implode(':', $params);
    }
}
```

## 📡 API Controller Structure

### Controller Template
```php
// app/Http/Controllers/Api/CourseController.php
namespace App\Http\Controllers\Api;

use App\DTOs\Course\CreateCourseDTO;
use App\DTOs\Course\UpdateCourseDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courseService
    ) {}
    
    /**
     * Display a listing of courses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['category_id', 'status', 'search', 'per_page']);
            $courses = $this->courseService->getAllCourses($filters);
            
            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store a newly created course
     */
    public function store(CreateCourseRequest $request): JsonResponse
    {
        try {
            $dto = new CreateCourseDTO(
                title: $request->title,
                description: $request->description,
                price: $request->price,
                categoryId: $request->category_id,
                instructorId: $request->instructor_id
            );
            
            $course = $this->courseService->createCourse($dto);
            
            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display the specified course
     */
    public function show(int $id): JsonResponse
    {
        try {
            $course = $this->courseService->getCourseWithLessons($id);
            
            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update the specified course
     */
    public function update(UpdateCourseRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new UpdateCourseDTO(
                title: $request->title,
                description: $request->description,
                price: $request->price,
                categoryId: $request->category_id
            );
            
            $course = $this->courseService->updateCourse($id, $dto);
            
            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified course
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->courseService->deleteCourse($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Request Validation Pattern
```php
// app/Http/Requests/Course/CreateCourseRequest.php
namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:users,id'
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Course title is required',
            'category_id.exists' => 'Selected category does not exist',
            'instructor_id.exists' => 'Selected instructor does not exist'
        ];
    }
}
```

## 📊 Model Design Patterns

### Model Structure Template
```php
// app/Models/Course.php
namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes, TenantAware;
    
    protected $fillable = [
        'title',
        'description',
        'price',
        'status',
        'category_id',
        'instructor_id',
        'tenant_id'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    protected $appends = [
        'is_free',
        'lessons_count'
    ];
    
    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
    
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    // Accessors
    public function getIsFreeAttribute(): bool
    {
        return $this->price <= 0;
    }
    
    public function getLessonsCountAttribute(): int
    {
        return $this->lessons()->count();
    }
    
    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
    
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    public function scopeByInstructor($query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }
    
    // Methods
    public function isEnrolledBy(User $user): bool
    {
        return $this->enrollments()
                   ->where('user_id', $user->id)
                   ->where('status', 'active')
                   ->exists();
    }
    
    public function publish(): bool
    {
        return $this->update(['status' => 'published']);
    }
    
    public function unpublish(): bool
    {
        return $this->update(['status' => 'draft']);
    }
}
```

## 🗃️ Database Architecture

### Migration Patterns
```php
// database/migrations/create_courses_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('thumbnail')->nullable();
            $table->json('metadata')->nullable();
            
            // Foreign keys
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['instructor_id', 'status']);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
```

### Factory Pattern
```php
// database/factories/CourseFactory.php
namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;
    
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $this->faker->randomFloat(2, 0, 500),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'category_id' => Category::factory(),
            'instructor_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
        ];
    }
    
    public function published(): Factory
    {
        return $this->state(fn () => ['status' => 'published']);
    }
    
    public function free(): Factory
    {
        return $this->state(fn () => ['price' => 0]);
    }
}
```

## 🔐 Authentication & Authorization

### Sanctum Configuration
```php
// app/Http/Controllers/Auth/AuthController.php
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
        
        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'Login successful'
        ]);
    }
    
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
}
```

### Permission-Based Authorization
```php
// app/Http/Middleware/CheckPermission.php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if (!auth()->user()->hasPermissionTo($permission)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        return $next($request);
    }
}

// Usage in routes
Route::middleware(['auth:sanctum', 'permission:manage-courses'])
     ->group(function () {
         Route::post('/courses', [CourseController::class, 'store']);
         Route::put('/courses/{id}', [CourseController::class, 'update']);
         Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
     });
```

## 💾 Caching Strategy

### Cache Service Pattern
```php
// app/Services/Cache/CacheService.php
namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $tenantKey = $this->getTenantKey($key);
        return Cache::remember($tenantKey, $ttl ?? self::DEFAULT_TTL, $callback);
    }
    
    public function forget(string $key): bool
    {
        $tenantKey = $this->getTenantKey($key);
        return Cache::forget($tenantKey);
    }
    
    public function forgetPattern(string $pattern): void
    {
        $tenantPattern = $this->getTenantKey($pattern);
        $keys = Cache::get($tenantPattern . '*');
        
        if ($keys) {
            Cache::deleteMultiple($keys);
        }
    }
    
    private function getTenantKey(string $key): string
    {
        $tenantId = app('current-tenant')->id ?? 'global';
        return "tenant_{$tenantId}:{$key}";
    }
}

// Usage in Service
class CourseService extends BaseService
{
    public function __construct(
        private Course $courseModel,
        private CacheService $cacheService
    ) {}
    
    public function getCourseWithCache(int $id): Course
    {
        return $this->cacheService->remember(
            "course_{$id}",
            fn() => $this->courseModel->with(['lessons', 'category'])->findOrFail($id),
            3600
        );
    }
}
```

## 🚀 Building New Modules

### Step-by-Step Module Creation

#### 1. Create Model & Migration
```bash
php artisan make:model Assignment -m
php artisan make:factory AssignmentFactory
```

#### 2. Define Model Structure
```php
// app/Models/Assignment.php
class Assignment extends Model
{
    use HasFactory, SoftDeletes, TenantAware;
    
    protected $fillable = [
        'title', 'description', 'due_date', 'course_id', 'tenant_id'
    ];
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
```

#### 3. Create Service Layer
```php
// app/Services/Assignment/AssignmentService.php
class AssignmentService extends BaseService
{
    public function __construct(private Assignment $assignmentModel) {}
    
    // Implementation methods...
}
```

#### 4. Create Controller
```bash
php artisan make:controller Api/AssignmentController
```

#### 5. Create Request Validation
```bash
php artisan make:request Assignment/CreateAssignmentRequest
php artisan make:request Assignment/UpdateAssignmentRequest
```

#### 6. Create DTOs
```php
// app/DTOs/Assignment/CreateAssignmentDTO.php
readonly class CreateAssignmentDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public string $dueDate,
        public int $courseId
    ) {}
}
```

#### 7. Define Routes
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])
     ->prefix('v1')
     ->group(function () {
         Route::apiResource('assignments', AssignmentController::class);
     });
```

#### 8. Create Tests
```bash
php artisan make:test AssignmentControllerTest
php artisan make:test AssignmentServiceTest --unit
```

## 🧪 Testing Patterns

### Feature Test Template
```php
// tests/Feature/AssignmentControllerTest.php
class AssignmentControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->course = Course::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Set tenant context
        app()->instance('current-tenant', $this->tenant);
    }
    
    public function test_can_create_assignment(): void
    {
        $response = $this->actingAs($this->user)
                         ->withHeaders(['X-Tenant-ID' => $this->tenant->id])
                         ->postJson('/api/v1/assignments', [
                             'title' => 'Test Assignment',
                             'description' => 'Test Description',
                             'due_date' => now()->addDays(7)->toDateString(),
                             'course_id' => $this->course->id
                         ]);
        
        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'title' => 'Test Assignment'
                    ]
                ]);
        
        $this->assertDatabaseHas('assignments', [
            'title' => 'Test Assignment',
            'tenant_id' => $this->tenant->id
        ]);
    }
}
```

### Unit Test Template
```php
// tests/Unit/AssignmentServiceTest.php
class AssignmentServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private AssignmentService $assignmentService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->assignmentService = app(AssignmentService::class);
    }
    
    public function test_create_assignment(): void
    {
        $tenant = Tenant::factory()->create();
        app()->instance('current-tenant', $tenant);
        
        $course = Course::factory()->create(['tenant_id' => $tenant->id]);
        
        $dto = new CreateAssignmentDTO(
            title: 'Test Assignment',
            description: 'Test Description',
            dueDate: now()->addDays(7)->toDateString(),
            courseId: $course->id
        );
        
        $assignment = $this->assignmentService->createAssignment($dto);
        
        $this->assertInstanceOf(Assignment::class, $assignment);
        $this->assertEquals('Test Assignment', $assignment->title);
        $this->assertEquals($tenant->id, $assignment->tenant_id);
    }
}
```

## ✅ Best Practices

### 1. Code Organization
- **One Class, One Responsibility** - Each service handles one domain
- **Consistent Naming** - Follow Laravel conventions
- **Proper Namespacing** - Organize classes logically

### 2. Database Best Practices
- **Always use migrations** for schema changes
- **Add proper indexes** for query optimization
- **Use foreign key constraints** for data integrity
- **Implement soft deletes** for important data

### 3. API Design
- **Consistent response format** across all endpoints
- **Proper HTTP status codes** for different scenarios
- **Validation at request level** before service layer
- **Error handling** with meaningful messages

### 4. Security Practices
- **Validate all inputs** at request level
- **Use middleware** for authentication/authorization
- **Implement rate limiting** for API endpoints
- **Sanitize database queries** using Eloquent

### 5. Performance Optimization
- **Implement caching** for frequently accessed data
- **Use eager loading** to prevent N+1 queries
- **Database indexing** for common query patterns
- **Queue heavy operations** for better response times

### 6. Multi-Tenant Considerations
- **Always filter by tenant** in queries
- **Use tenant-aware traits** in models
- **Validate tenant access** in middleware
- **Isolate tenant data** completely

### Module Checklist
When creating a new module, ensure you have:

- [ ] **Model** with proper relationships and traits
- [ ] **Migration** with indexes and constraints
- [ ] **Factory** for testing data
- [ ] **Service** with business logic
- [ ] **Controller** with proper error handling
- [ ] **Request classes** for validation
- [ ] **DTOs** for data transfer
- [ ] **Routes** with proper middleware
- [ ] **Tests** for both feature and unit scenarios
- [ ] **Documentation** updates

---

## 📚 Additional Resources

### Laravel Documentation
- [Laravel 11.x Documentation](https://laravel.com/docs/11.x)
- [Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)
- [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum)

### Multi-Tenancy Resources
- [Spatie Laravel Multitenancy](https://spatie.be/docs/laravel-multitenancy)
- [Multi-Tenant Database Design](https://docs.microsoft.com/en-us/azure/sql-database/saas-tenancy-app-design-patterns)

### Testing Resources
- [Laravel Testing](https://laravel.com/docs/11.x/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

**Developer:** Sanu Khan [@sanukhandev](https://github.com/sanukhandev)

*This guide serves as the foundation for building consistent, scalable, and maintainable backend modules for the LMS SaaS platform.*
