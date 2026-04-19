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
    Schema::table('workers', function (Blueprint $table) {
        $table->integer('completed_jobs_count')->default(0)->after('avg_price');
        $table->boolean('is_payment_pending')->default(false)->after('completed_jobs_count');
        $table->decimal('total_amount_due', 10, 2)->default(0.00)->after('is_payment_pending');
        $table->decimal('total_amount_paid', 10, 2)->default(0.00)->after('total_amount_due');
    });
}

public function down(): void
{
    Schema::table('workers', function (Blueprint $table) {
        $table->dropColumn([
            'completed_jobs_count',
            'is_payment_pending',
            'total_amount_due',
            'total_amount_paid',
        ]);
    });
}
};
