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
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');

            $table->enum('mode', ['per_session', 'full_course'])->default('per_session');
            $table->decimal('default_session_rate', 10, 2)->default(0); // AED
            $table->boolean('enable_student_payment')->default(true);
            $table->boolean('enable_instructor_payout')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
