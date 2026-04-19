<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('ratings', function (Blueprint $table) {
        // Drop foreign keys first
        $table->dropForeign(['user_id']);
        $table->dropForeign(['worker_id']);

        $table->dropUnique('ratings_user_id_worker_id_unique');

        // Add request_id
        $table->foreignId('request_id')
            ->after('id')
            ->constrained('requests')
            ->cascadeOnDelete();

        // Re-add foreign keys
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('worker_id')->references('id')->on('workers')->cascadeOnDelete();

        // New unique constraint
        $table->unique(['user_id', 'worker_id', 'request_id']);
    });
}

public function down(): void
{
    Schema::table('ratings', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropForeign(['worker_id']);
        $table->dropUnique(['user_id', 'worker_id', 'request_id']);
        $table->dropForeign(['request_id']);
        $table->dropColumn('request_id');
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('worker_id')->references('id')->on('workers')->cascadeOnDelete();
        $table->unique(['user_id', 'worker_id']);
    });
}
};