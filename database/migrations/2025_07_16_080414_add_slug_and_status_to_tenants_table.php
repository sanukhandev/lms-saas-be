<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('settings');
            }
        });

        // Update existing tenants to have slugs (SQLite compatible)
        $tenants = DB::table('tenants')->whereNull('slug')->orWhere('slug', '')->get();
        foreach ($tenants as $tenant) {
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['slug' => 'tenant-' . $tenant->id]);
        }

        // Check if unique constraint exists before adding it
        $indexes = DB::select('SHOW INDEX FROM tenants WHERE Key_name = ?', ['tenants_slug_unique']);
        if (empty($indexes)) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Drop unique constraint if it exists
            try {
                $table->dropUnique(['slug']);
            } catch (Exception $e) {
                // Ignore if constraint doesn't exist
            }

            // Drop columns if they exist
            if (Schema::hasColumn('tenants', 'slug')) {
                $table->dropColumn('slug');
            }
            if (Schema::hasColumn('tenants', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
