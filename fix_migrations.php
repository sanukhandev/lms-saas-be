<?php

/**
 * Fresh Migration Setup Script
 * This script helps you start fresh with clean migrations
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Fresh Migration Setup ===\n\n";

function confirmAction($message)
{
    echo $message . " (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return strtolower(trim($line)) === 'y';
}

// Step 1: Check current state
echo "1. Checking current database state...\n";
$tables = DB::select('SHOW TABLES');
$tableCount = count($tables);
echo "   Found $tableCount tables in the database\n";

// Step 2: Show migration status
echo "\n2. Current migration status:\n";
$migrations = DB::table('migrations')->orderBy('id')->get();
echo "   Total migrations in database: " . $migrations->count() . "\n";
echo "   Latest batch: " . $migrations->max('batch') . "\n";

// Step 3: Options for fresh start
echo "\n3. Fresh Migration Options:\n";
echo "   a) Use 'php artisan migrate:fresh' - Drops all tables and re-runs migrations\n";
echo "   b) Use 'php artisan migrate:fresh --seed' - Same as above but with seeders\n";
echo "   c) Manual cleanup - Remove specific duplicate migration files\n";

echo "\nWhat would you like to do?\n";
echo "   1. Run migrate:fresh (recommended for development)\n";
echo "   2. Run migrate:fresh --seed\n";
echo "   3. Show duplicate migration files for manual cleanup\n";
echo "   4. Exit\n";

echo "\nEnter your choice (1-4): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

switch ($choice) {
    case '1':
        if (confirmAction("This will DROP ALL TABLES and re-run migrations. Continue?")) {
            echo "\nRunning migrate:fresh...\n";
            exec('php artisan migrate:fresh', $output, $return_var);
            foreach ($output as $line) {
                echo $line . "\n";
            }
            if ($return_var === 0) {
                echo "\n✓ Fresh migration completed successfully!\n";
            } else {
                echo "\n✗ Migration failed. Check the output above.\n";
            }
        }
        break;

    case '2':
        if (confirmAction("This will DROP ALL TABLES, re-run migrations, and run seeders. Continue?")) {
            echo "\nRunning migrate:fresh --seed...\n";
            exec('php artisan migrate:fresh --seed', $output, $return_var);
            foreach ($output as $line) {
                echo $line . "\n";
            }
            if ($return_var === 0) {
                echo "\n✓ Fresh migration with seeders completed successfully!\n";
            } else {
                echo "\n✗ Migration failed. Check the output above.\n";
            }
        }
        break;

    case '3':
        echo "\nDuplicate migration files found:\n";
        $migrationFiles = glob('database/migrations/*.php');
        $duplicates = [];

        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            if (preg_match('/2025_07_20_.*/', $filename)) {
                $duplicates[] = $filename;
            }
        }

        if (empty($duplicates)) {
            echo "   No obvious duplicate files found.\n";
        } else {
            foreach ($duplicates as $duplicate) {
                echo "   - $duplicate\n";
            }
            echo "\nYou can manually delete these files if they're duplicates.\n";
        }
        break;

    case '4':
        echo "Exiting...\n";
        break;

    default:
        echo "Invalid choice. Exiting...\n";
        break;
}

echo "\nDone!\n";
