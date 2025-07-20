<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // customer
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->json('billing_address')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'user_id']);
            $table->index(['status']);
            $table->index(['issue_date']);
            $table->index(['due_date']);
            $table->index(['total_amount']);
            $table->index(['invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
