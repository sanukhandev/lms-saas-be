<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // stripe, paypal, razorpay, etc.
            $table->boolean('is_active')->default(false);
            $table->boolean('is_test_mode')->default(true);
            $table->json('credentials'); // encrypted gateway credentials
            $table->json('settings')->nullable(); // gateway specific settings
            $table->decimal('commission_rate', 5, 2)->default(0); // platform commission %
            $table->string('currency', 3)->default('USD');
            $table->json('supported_currencies')->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'gateway']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['gateway', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
