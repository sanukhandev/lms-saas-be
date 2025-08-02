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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // optional
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');

            $table->string('title');
            $table->text('message')->nullable();

            $table->enum('channel', ['email', 'sms', 'in_app'])->default('in_app');
            $table->boolean('is_sent')->default(false);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
