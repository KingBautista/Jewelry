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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('payment_type'); // downpayment, monthly, full, partial, refund, reversal, custom
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('expected_amount', 10, 2)->nullable();
            $table->string('reference_number');
            $table->json('receipt_images')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'confirmed'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->date('payment_date');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->enum('source', ['customer_submission', 'admin_created'])->default('admin_created');
            $table->json('selected_schedules')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};