<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // student
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('student_payments')->onDelete('set null');
            $table->string('purchase_number')->unique();
            $table->decimal('original_price', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('final_price', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('access_type', ['lifetime', 'time_limited', 'subscription'])->default('lifetime');
            $table->timestamp('access_expires_at')->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'course_id']); // one purchase per user per course
            $table->index(['tenant_id', 'user_id']);
            $table->index(['course_id', 'status']);
            $table->index(['status']);
            $table->index(['purchase_number']);
            $table->index(['purchased_at']);
            $table->index(['access_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_purchases');
    }
};
