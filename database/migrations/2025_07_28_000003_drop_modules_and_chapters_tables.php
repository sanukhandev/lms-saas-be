<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop modules table if it exists
        if (Schema::hasTable('modules')) {
            Schema::dropIfExists('modules');
        }
        
        // Drop chapters table if it exists
        if (Schema::hasTable('chapters')) {
            Schema::dropIfExists('chapters');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This is intentionally empty as recreating the tables would require additional logic
     * and is not necessary for the migration's purpose
     */
    public function down(): void
    {
        // Do nothing as we're moving to a tree structure
    }
};
