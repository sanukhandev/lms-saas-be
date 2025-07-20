# LMS SaaS Backend API

A comprehensive Learning Management System (LMS) backend built with Laravel, featuring multi-tenant architecture, role-based access control, and comprehensive course management capabilities.

![Laravel](https://img.shields.io/badge/Laravel-12.x-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange?style=flat-square&logo=mysql)
![Redis](https://img.shields.io/badge/Redis-Cache-red?style=flat-square&logo=redis)

## 🚀 Features

### 🏢 Multi-Tenant Architecture
- **Complete Tenant Isolation** - Data segregation and security
- **Per-Tenant Configuration** - Custom settings and themes
- **Subdomain/Domain Routing** - Automatic tenant detection
- **Tenant-Specific Branding** - Custom logos and color schemes

### 👥 User Management
- **Role-Based Access Control** - Admin, Instructor, Student roles
- **Spatie Permissions** - Granular permission system
- **Authentication** - Laravel Sanctum for API authentication
- **Multi-Tenant User Isolation** - Users belong to specific tenants

### 📚 Course Management
- **Course Builder** - Visual course creation tools
- **Content Management** - Videos, documents, quizzes
- **Categorization** - Hierarchical course categories
- **Pricing & Enrollment** - Flexible pricing models
- **Progress Tracking** - Student progress analytics

### 📊 Analytics & Reporting
- **Student Analytics** - Progress and engagement metrics
- **Course Analytics** - Completion rates and performance
- **Revenue Analytics** - Sales and financial reporting
- **Custom Dashboards** - Configurable analytics views

### 🔧 Advanced Features
- **Cache Management** - Redis-based caching system
- **API Versioning** - RESTful API with version control
- **File Management** - Secure file upload and storage
- **Notification System** - Real-time notifications
- **Payment Integration** - Course purchase and billing

## 🛠️ Tech Stack

**Framework:** [Laravel 12.x](https://laravel.com/) with PHP 8.2+

**Database:** MySQL 8.0+ with Laravel Eloquent ORM

**Cache:** [Redis](https://redis.io/) for session and application caching

**Authentication:** [Laravel Sanctum](https://laravel.com/docs/sanctum) for SPA authentication

**Permissions:** [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) for role-based access

**Queue:** Laravel Queue system for background job processing

**File Storage:** Laravel File System with cloud storage support

**API:** RESTful API with versioning support

## 📁 Project Structure

```
app/
├── Console/              # Artisan commands
├── DTOs/                # Data Transfer Objects
├── Exceptions/          # Custom exception handlers
├── Http/                # HTTP layer
│   ├── Controllers/     # API controllers
│   │   ├── Api/        # Main API controllers
│   │   └── V1/         # Version 1 controllers
│   ├── Middleware/     # Custom middleware
│   └── Requests/       # Form request validation
├── Models/             # Eloquent models
│   └── Scopes/        # Query scopes
├── Providers/          # Service providers
├── Services/           # Business logic services
│   ├── Analytics/      # Analytics services
│   ├── Auth/          # Authentication services
│   ├── Cache/         # Cache management
│   ├── Category/      # Category management
│   ├── Course/        # Course management
│   ├── CourseBuilder/ # Course building tools
│   ├── Dashboard/     # Dashboard services
│   ├── Tenant/        # Multi-tenant services
│   └── User/          # User management
├── Traits/            # Reusable traits
└── Utils/             # Utility classes

database/
├── factories/         # Model factories
├── migrations/        # Database migrations
└── seeders/          # Database seeders

routes/
├── api.php           # API routes
├── web.php           # Web routes
└── console.php       # Console routes
```

## 🚀 Getting Started

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0+
- Redis server
- Node.js & NPM (for asset compilation)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/sanukhandev/lms-saas-be.git
cd lms-saas-be
```

2. **Install dependencies**
```bash
composer install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure environment variables**
```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_saas
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
```

5. **Database setup**
```bash
php artisan migrate
php artisan db:seed
```

6. **Storage linking**
```bash
php artisan storage:link
```

7. **Start the development server**
```bash
php artisan serve
```

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
All protected routes require Bearer token authentication:
```bash
Authorization: Bearer {your-token}
```

### Multi-Tenant Headers
For tenant-specific requests:
```bash
X-Tenant-ID: {tenant-id}
X-Tenant-Domain: {tenant-domain}
```

### Core Endpoints

#### Authentication
```bash
POST /v1/auth/register     # User registration
POST /v1/auth/login        # User login
POST /v1/auth/logout       # User logout
POST /v1/auth/refresh      # Token refresh
```

#### Tenant Management
```bash
GET /v1/tenants                    # List tenants (admin)
GET /v1/tenants/current           # Current tenant info
GET /v1/tenants/domain/{domain}   # Get tenant by domain
PUT /v1/tenants/{domain}/settings # Update tenant settings
```

#### Course Management
```bash
GET /v1/courses              # List courses
POST /v1/courses             # Create course
GET /v1/courses/{id}         # Get course details
PUT /v1/courses/{id}         # Update course
DELETE /v1/courses/{id}      # Delete course
```

#### Category Management
```bash
GET /v1/categories           # List categories
POST /v1/categories          # Create category
PUT /v1/categories/{id}      # Update category
DELETE /v1/categories/{id}   # Delete category
```

#### User Management
```bash
GET /v1/dashboard/users      # List users
GET /v1/dashboard/users/stats # User statistics
```

For complete API documentation, see [DASHBOARD_API_DOCUMENTATION.md](DASHBOARD_API_DOCUMENTATION.md)

## 🏗️ Architecture Patterns

### Service Layer Architecture
The application follows a service-oriented architecture with clear separation of concerns:

- **Controllers** - Handle HTTP requests and responses
- **Services** - Contain business logic and operations
- **Models** - Represent data structures and relationships
- **DTOs** - Transfer data between layers
- **Requests** - Validate incoming data

### Multi-Tenant Implementation
```php
// Tenant detection middleware
class TenantAccessMiddleware
{
    public function handle($request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $domain = $request->header('X-Tenant-Domain');
        
        // Set tenant context
        app()->instance('current-tenant', $tenant);
        
        return $next($request);
    }
}
```

### Repository Pattern
```php
// Example service structure
class CourseService
{
    public function __construct(
        private CourseRepository $courseRepository,
        private CategoryRepository $categoryRepository
    ) {}
    
    public function createCourse(CreateCourseDTO $dto): Course
    {
        // Business logic here
        return $this->courseRepository->create($dto->toArray());
    }
}
```

## 🔒 Security Features

### Multi-Tenant Security
- **Data Isolation** - Complete separation of tenant data
- **Access Control** - Tenant-specific user authentication
- **Domain Validation** - Secure tenant identification

### API Security
- **Sanctum Authentication** - Secure API token management
- **Role-Based Permissions** - Granular access control
- **Request Validation** - Input sanitization and validation
- **Rate Limiting** - API request throttling

### Data Protection
- **Eloquent Scopes** - Automatic tenant filtering
- **Soft Deletes** - Data recovery and audit trails
- **Encrypted Storage** - Sensitive data encryption

## 📊 Caching Strategy

### Redis Implementation
```php
// Cache configuration
'redis' => [
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
],
```

### Caching Patterns
- **Query Caching** - Database query optimization
- **Session Storage** - Redis-based session management
- **Application Cache** - Frequently accessed data
- **API Response Caching** - Improved response times

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Structure
```
tests/
├── Feature/           # Integration tests
├── Unit/             # Unit tests
└── TestCase.php      # Base test class
```

## 📋 Available Scripts

```bash
# Development
php artisan serve              # Start development server
php artisan queue:work         # Start queue worker
php artisan schedule:work      # Start task scheduler

# Database
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Fresh migration
php artisan db:seed          # Run seeders

# Cache Management
php artisan cache:clear      # Clear application cache
php artisan config:clear     # Clear config cache
php artisan route:clear      # Clear route cache

# Code Quality
php artisan pint            # Code formatting
./vendor/bin/phpunit        # Run tests
```

## 📖 Documentation

### Architecture & Development
- **[Backend Design & Code Structure Guide](BACKEND_DESIGN_GUIDE.md)** - Complete backend architecture and development patterns
- **[Dashboard API Documentation](DASHBOARD_API_DOCUMENTATION.md)** - Complete API reference
- **[Multi-Tenant Setup](MULTITENANT_SETUP.md)** - Multi-tenancy implementation guide
- **[Analytics Implementation](ANALYTICS_IMPLEMENTATION_SUMMARY.md)** - Analytics system overview

### Features & Updates
- **[Dashboard Updates](DASHBOARD_UPDATES.md)** - Recent dashboard improvements
- **[Redis Cache Implementation](REDIS_CACHE_IMPLEMENTATION.md)** - Caching system guide
- **[Auth & Tenant Refactoring](AUTH_TENANT_REFACTORING.md)** - Authentication system changes
- **[Changelog](CHANGELOG.md)** - Version history and updates

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="LMS SaaS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms_saas
DB_USERNAME=root
DB_PASSWORD=

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### CORS Configuration
```php
// config/cors.php
'allowed_origins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
],
```

## 🚀 Deployment

### Production Setup
1. **Server Requirements**
   - PHP 8.2+
   - MySQL 8.0+
   - Redis Server
   - Nginx/Apache

2. **Environment Configuration**
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Configure database
DB_HOST=your-production-host
DB_DATABASE=your-production-db

# Set cache drivers
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

3. **Optimization Commands**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

## 👨‍💻 Developer

**Sanu Khan** - [@sanukhandev](https://github.com/sanukhandev)

*Building scalable LMS SaaS solutions with Laravel*

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Related Projects

- **[LMS Frontend Admin](../shadcn-admin/)** - React-based admin dashboard
- **Frontend Documentation** - Module design and architecture guides

For questions, issues, or contributions, please reach out through GitHub issues or connect with me on GitHub.

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
