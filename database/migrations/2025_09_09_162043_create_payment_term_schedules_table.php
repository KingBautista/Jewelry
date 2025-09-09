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
        Schema::create('payment_term_schedules', function (Blueprint $table) {
            $table->engine = "InnoDB";
            
            $table->bigIncrements('id');
            $table->bigInteger('payment_term_id')->unsigned();
            $table->integer('month_number'); // e.g., 1, 2, 3, 4, 5
            $table->decimal('percentage', 5, 2); // e.g., 10.00, 20.00, 22.00, 15.00, 3.00
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('payment_term_id')->references('id')->on('payment_terms')->onDelete('cascade');
            $table->index(['payment_term_id', 'month_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_term_schedules');
    }
};