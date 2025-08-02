<?php

/**
 * Complete Fresh Setup Script for LMS Backend
 * This script provides a comprehensive fresh start solution
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

echo "=== LMS Backend Fresh Setup ===\n\n";

function runCommand($command, $description) {
    echo "Running: $description\n";
    echo "Command: $command\n";
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    foreach ($output as $line) {
        echo "  $line\n";
    }
    
    if ($return_var === 0) {
        echo "✓ Success\n\n";
        return true;
    } else {
        echo "✗ Failed (exit code: $return_var)\n\n";
        return false;
    }
}

function checkModelFactories() {
    echo "Checking Model Factories:\n";
    $factoryDir = 'database/factories';
    
    if (!is_dir($factoryDir)) {
        echo "  ✗ Factory directory not found\n\n";
        return false;
    }
    
    $factories = glob("$factoryDir/*.php");
    $requiredFactories = [
        'UserFactory.php',
        'TenantFactory.php',
        'CategoryFactory.php',
        'CourseFactory.php'
    ];
    
    $found = [];
    foreach ($factories as $factory) {
        $found[] = basename($factory);
    }
    
    foreach ($requiredFactories as $required) {
        if (in_array($required, $found)) {
            echo "  ✓ $required found\n";
        } else {
            echo "  ✗ $required missing\n";
        }
    }
    
    echo "\n";
    return true;
}

function checkSeederDependencies() {
    echo "Checking Seeder Dependencies:\n";
    
    $models = [
        'User' => 'App\\Models\\User',
        'Tenant' => 'App\\Models\\Tenant', 
        'Category' => 'App\\Models\\Category',
        'Course' => 'App\\Models\\Course',
        'CourseContent' => 'App\\Models\\CourseContent',
    ];
    
    foreach ($models as $name => $class) {
        if (class_exists($class)) {
            echo "  ✓ Model $name exists\n";
        } else {
            echo "  ✗ Model $name missing\n";
        }
    }
    
    echo "\n";
    return true;
}

function showDuplicateMigrations() {
    echo "Duplicate Migration Files:\n";
    $migrationFiles = glob('database/migrations/*.php');
    $duplicates = [];
    
    $patterns = [
        'create_users_table',
        'create_categories_table',
        'create_courses_table',
        'create_tenants_table',
        'create_sessions_table'
    ];
    
    foreach ($patterns as $pattern) {
        $matches = [];
        foreach ($migrationFiles as $file) {
            if (strpos(basename($file), $pattern) !== false) {
                $matches[] = basename($file);
            }
        }
        
        if (count($matches) > 1) {
            echo "  Pattern '$pattern':\n";
            foreach ($matches as $match) {
                echo "    - $match\n";
            }
            $duplicates = array_merge($duplicates, $matches);
        }
    }
    
    if (empty($duplicates)) {
        echo "  ✓ No obvious duplicates found\n";
    }
    
    echo "\n";
    return $duplicates;
}

// Main execution
echo "1. Current Database Status\n";
try {
    $tables = collect(DB::select('SHOW TABLES'));
    echo "  Database connected: ✓\n";
    echo "  Tables found: " . $tables->count() . "\n";
    
    if ($tables->count() > 0) {
        $migrations = DB::table('migrations')->count();
        echo "  Migrations in DB: $migrations\n";
    }
} catch (Exception $e) {
    echo "  Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2. Migration Analysis\n";
$duplicates = showDuplicateMigrations();

echo "3. Model & Factory Check\n";
checkModelFactories();

echo "4. Seeder Dependencies\n";
checkSeederDependencies();

echo "5. Fresh Setup Options\n";
echo "Choose an option:\n";
echo "  1. Fresh migrate only (drop all tables, run migrations)\n";
echo "  2. Fresh migrate with seed (drop all, migrate, seed)\n";
echo "  3. Reset migrations table only\n";
echo "  4. Show duplicate files for manual cleanup\n";
echo "  5. Run diagnostic only (no changes)\n";
echo "  6. Exit\n";

echo "\nEnter choice (1-6): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

switch ($choice) {
    case '1':
        echo "\nThis will DROP ALL TABLES and re-run migrations.\n";
        echo "Continue? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirm) === 'y') {
            runCommand('php artisan migrate:fresh', 'Fresh migration');
            echo "✓ Fresh migration completed!\n";
            echo "Next steps:\n";
            echo "  - Run 'php artisan db:seed' to populate data\n";
            echo "  - Or run 'php artisan serve' to start the server\n";
        }
        break;
        
    case '2':
        echo "\nThis will DROP ALL TABLES, re-run migrations, and seed data.\n";
        echo "Continue? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirm) === 'y') {
            runCommand('php artisan migrate:fresh --seed', 'Fresh migration with seeding');
            echo "✓ Fresh migration with seeding completed!\n";
            echo "Next steps:\n";
            echo "  - Run 'php artisan serve' to start the server\n";
            echo "  - Test login with admin@demo.com\n";
        }
        break;
        
    case '3':
        echo "\nThis will clear the migrations table only.\n";
        echo "Continue? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirm) === 'y') {
            DB::table('migrations')->truncate();
            echo "✓ Migrations table cleared!\n";
            echo "Next steps:\n";
            echo "  - Run 'php artisan migrate' to apply migrations\n";
        }
        break;
        
    case '4':
        echo "\nDuplicate migration files found:\n";
        if (!empty($duplicates)) {
            foreach ($duplicates as $duplicate) {
                echo "  database/migrations/$duplicate\n";
            }
            echo "\nTo clean up, you can delete the older duplicate files.\n";
        }
        break;
        
    case '5':
        echo "Diagnostic completed. No changes made.\n";
        break;
        
    case '6':
        echo "Exiting...\n";
        break;
        
    default:
        echo "Invalid choice.\n";
        break;
}

echo "\nScript completed!\n";
