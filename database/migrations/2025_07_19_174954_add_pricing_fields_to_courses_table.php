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
            // Access model and pricing fields
            $table->enum('access_model', ['one_time', 'monthly_subscription', 'full_curriculum'])
                  ->default('one_time')
                  ->after('description');
            
            // One-time purchase pricing
            $table->decimal('price', 10, 2)->nullable()->after('access_model');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('price');
            
            // Subscription pricing
            $table->decimal('subscription_price', 10, 2)->nullable()->after('discount_percentage');
            $table->integer('trial_period_days')->nullable()->after('subscription_price');
            
            // Publishing and activation
            $table->enum('status', ['draft', 'published', 'archived'])
                  ->default('draft')
                  ->after('trial_period_days');
            $table->boolean('is_active')->default(false)->after('status');
            $table->boolean('is_pricing_active')->default(false)->after('is_active');
            
            // Add indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'access_model']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'access_model']);
            $table->dropIndex(['tenant_id', 'is_active']);
            
            $table->dropColumn([
                'access_model',
                'price',
                'discount_percentage',
                'subscription_price',
                'trial_period_days',
                'status',
                'is_active',
                'is_pricing_active'
            ]);
        });
    }
};
