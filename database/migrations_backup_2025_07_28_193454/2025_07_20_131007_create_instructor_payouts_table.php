<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->string('payout_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('method', ['bank_transfer', 'paypal', 'stripe', 'manual'])->default('bank_transfer');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('bank_details')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'instructor_id']);
            $table->index(['instructor_id', 'status']);
            $table->index(['status']);
            $table->index(['period_start', 'period_end']);
            $table->index(['payout_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_payouts');
    }
};
