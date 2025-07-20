<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // recipient
            $table->string('type'); // notification type class
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // additional notification data
            $table->enum('channel', ['database', 'email', 'sms', 'push'])->default('database');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('related_type')->nullable(); // morphable type
            $table->unsignedBigInteger('related_id')->nullable(); // morphable id
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
            $table->index(['type']);
            $table->index(['channel']);
            $table->index(['priority']);
            $table->index(['related_type', 'related_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
