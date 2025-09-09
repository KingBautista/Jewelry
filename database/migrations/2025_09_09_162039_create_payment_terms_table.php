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
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->engine = "InnoDB";
            
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('down_payment_percentage', 5, 2)->default(0); // e.g., 30.00 for 30%
            $table->decimal('remaining_percentage', 5, 2)->default(100); // e.g., 70.00 for 70%
            $table->integer('term_months')->default(1); // e.g., 5 months
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};