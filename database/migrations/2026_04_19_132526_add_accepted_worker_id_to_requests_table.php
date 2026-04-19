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
    Schema::table('requests', function (Blueprint $table) {
        $table->dropForeign(['worker_id']);
        $table->dropColumn('worker_id');

        $table->foreignId('accepted_worker_id')
            ->nullable()
            ->after('job_type_id')
            ->constrained('workers')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('requests', function (Blueprint $table) {
        $table->dropForeign(['accepted_worker_id']);
        $table->dropColumn('accepted_worker_id');

        $table->foreignId('worker_id')
            ->nullable()
            ->constrained('workers')
            ->cascadeOnDelete();
    });
}
};
