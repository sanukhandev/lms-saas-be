<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0); // percentage
            $table->decimal('discount_rate', 5, 2)->default(0); // percentage
            $table->timestamps();

            // Indexes
            $table->index(['invoice_id']);
            $table->index(['course_id']);
            $table->index(['total_price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
