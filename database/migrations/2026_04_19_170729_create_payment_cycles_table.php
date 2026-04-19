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
    Schema::create('payment_cycles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
        $table->integer('cycle_number');           // 1, 2, 3...
        $table->integer('completed_jobs')->default(0); // how many jobs in this cycle
        $table->decimal('amount_due', 8, 2)->default(75.00); // 5 * 15 EGP
        $table->decimal('amount_paid', 8, 2)->default(0.00);
        $table->enum('status', ['pending', 'proof_uploaded', 'paid', 'rejected'])->default('pending');
        $table->string('proof_image')->nullable();  // screenshot path
        $table->timestamp('proof_uploaded_at')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamp('cycle_started_at')->nullable();
        $table->timestamp('cycle_ended_at')->nullable();
        $table->timestamps();

        $table->unique(['worker_id', 'cycle_number']);
    });
}

public function down(): void
{
    Schema::dropIfExists('payment_cycles');
}
};
