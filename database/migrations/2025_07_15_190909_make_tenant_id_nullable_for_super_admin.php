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
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['tenant_id']);
            
            // Drop the unique constraint
            $table->dropUnique(['tenant_id', 'email']);
            
            // Make tenant_id nullable
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            
            // Re-add the foreign key constraint (nullable)
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Create a new unique constraint that handles nulls properly
            $table->unique(['tenant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the constraints
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['tenant_id', 'email']);
            
            // Make tenant_id not nullable again
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            
            // Re-add the original constraints
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'email']);
        });
    }
};
