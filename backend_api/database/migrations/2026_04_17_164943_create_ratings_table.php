<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->unsignedTinyInteger('rate');            // 1–5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'worker_id']);       // one rating per user per worker
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};