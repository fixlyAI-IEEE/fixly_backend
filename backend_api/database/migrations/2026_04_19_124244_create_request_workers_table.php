<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->enum('status', ['pending', 'offered', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['request_id', 'worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_workers');
    }
};