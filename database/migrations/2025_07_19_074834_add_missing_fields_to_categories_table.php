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
        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('parent_id');
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->string('image_url')->nullable()->after('sort_order');
            $table->text('meta_description')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'is_active', 
                'sort_order',
                'image_url',
                'meta_description'
            ]);
        });
    }
};
