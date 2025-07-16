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
            $table->string('slug')->nullable()->after('name');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('settings');
        });

        // Update existing tenants to have slugs (SQLite compatible)
        $tenants = DB::table('tenants')->whereNull('slug')->orWhere('slug', '')->get();
        foreach ($tenants as $tenant) {
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['slug' => 'tenant-' . $tenant->id]);
        }

        // Now make slug unique
        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slug', 'status']);
        });
    }
};
