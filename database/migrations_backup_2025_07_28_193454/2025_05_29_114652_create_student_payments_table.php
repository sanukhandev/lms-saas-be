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
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('class_session_id')->nullable()->constrained()->onDelete('set null');

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('AED');
            $table->enum('status', ['paid', 'pending', 'failed'])->default('pending');
            $table->string('payment_method')->nullable(); // card, cash, stripe
            $table->string('transaction_ref')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'student_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};
