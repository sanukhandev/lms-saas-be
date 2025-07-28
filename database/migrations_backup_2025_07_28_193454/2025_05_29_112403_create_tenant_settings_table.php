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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');

            $table->integer('default_class_duration')->default(60); // in minutes
            $table->enum('class_type', ['1v1', '1vN', 'NvN'])->default('1vN');

            $table->boolean('enable_certificates')->default(true);
            $table->string('certificate_template')->nullable(); // blade template slug

            $table->boolean('enable_recordings')->default(false);
            $table->enum('recording_source', ['youtube', 'jitsi', 'none'])->default('none');

            $table->boolean('enable_invoices')->default(true);
            $table->enum('billing_mode', ['per_session', 'per_course'])->default('per_session');

            $table->boolean('enable_exams')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
