<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // student
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('method', ['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'stripe', 'manual'])->default('credit_card');
            $table->string('gateway')->nullable(); // payment gateway used
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable(); // full gateway response
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id']);
            $table->index(['invoice_id']);
            $table->index(['status']);
            $table->index(['payment_number']);
            $table->index(['gateway_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
