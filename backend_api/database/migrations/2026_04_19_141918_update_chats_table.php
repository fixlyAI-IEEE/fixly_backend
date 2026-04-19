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
    Schema::table('chats', function (Blueprint $table) {
        // Make job_type_id optional — not known at conversation start
        $table->foreignId('job_type_id')->nullable()->change();

        // Track who sent the message: 'user' or 'assistant'
        $table->enum('role', ['user', 'assistant'])->after('user_id')->default('user');
    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::table('chats', function (Blueprint $table) {
        $table->foreignId('job_type_id')->nullable(false)->change();
        $table->dropColumn('role');
    });
}
};
