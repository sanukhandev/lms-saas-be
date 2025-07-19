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
        Schema::table('courses', function (Blueprint $table) {
            // Course status and publishing
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('description');
            $table->boolean('is_active')->default(false)->after('status');
            
            // Access model for course
            $table->enum('access_model', ['one_time', 'monthly_subscription', 'full_curriculum'])->default('one_time')->after('is_active');
            
            // Pricing fields
            $table->decimal('price', 10, 2)->nullable()->after('access_model');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('price');
            $table->decimal('subscription_price', 10, 2)->nullable()->after('discount_percentage');
            $table->integer('trial_period_days')->nullable()->after('subscription_price');
            $table->boolean('is_pricing_active')->default(false)->after('trial_period_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'is_active',
                'access_model',
                'price',
                'discount_percentage',
                'subscription_price',
                'trial_period_days',
                'is_pricing_active'
            ]);
        });
    }
};
