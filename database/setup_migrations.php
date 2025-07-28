<?php
// Script to move new migrations to the migrations folder

// Load Laravel environment
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$basePath = database_path();
$newMigrationsPath = $basePath . '/new_migrations';
$migrationsPath = $basePath . '/migrations';

// Check if the source directory exists
if (!is_dir($newMigrationsPath)) {
    echo "Error: Source directory not found: {$newMigrationsPath}\n";
    exit(1);
}

// Create backup of existing migrations
$backupPath = $basePath . '/migrations_backup_' . date('Y_m_d_His');
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

// Copy existing migrations to backup
$existingMigrations = scandir($migrationsPath);
foreach ($existingMigrations as $file) {
    if ($file !== '.' && $file !== '..' && is_file($migrationsPath . '/' . $file)) {
        copy($migrationsPath . '/' . $file, $backupPath . '/' . $file);
    }
}

echo "Backed up existing migrations to: {$backupPath}\n";

// Clear existing migrations folder
foreach ($existingMigrations as $file) {
    if ($file !== '.' && $file !== '..' && is_file($migrationsPath . '/' . $file)) {
        unlink($migrationsPath . '/' . $file);
    }
}

echo "Cleared existing migrations folder\n";

// Get new migrations and sort them
$newMigrations = scandir($newMigrationsPath);
$migrationFiles = [];

foreach ($newMigrations as $file) {
    if ($file !== '.' && $file !== '..' && is_file($newMigrationsPath . '/' . $file)) {
        $migrationFiles[] = $file;
    }
}

sort($migrationFiles);

// Create new timestamped migration files
$timestamp = date('Y_m_d_His');
$timestampIncrement = 0;

foreach ($migrationFiles as $file) {
    // Extract the descriptive part of the filename
    preg_match('/^\d+_(.*)\.php$/', $file, $matches);
    $description = $matches[1] ?? 'migration';
    
    // Create a new timestamp with an increment
    $newTimestamp = date('Y_m_d_His', strtotime("{$timestamp} +{$timestampIncrement} seconds"));
    $newFilename = "{$newTimestamp}_{$description}.php";
    
    // Copy the file with the new name
    copy($newMigrationsPath . '/' . $file, $migrationsPath . '/' . $newFilename);
    
    echo "Created migration: {$newFilename}\n";
    
    $timestampIncrement++;
}

echo "Migration process completed successfully\n";
