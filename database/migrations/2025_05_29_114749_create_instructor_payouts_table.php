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
        Schema::create('instructor_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_session_id')->nullable()->constrained()->onDelete('set null');

            $table->decimal('amount', 10, 2);
            $table->enum('status', ['unpaid', 'paid', 'processing'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'instructor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_payouts');
    }
};
