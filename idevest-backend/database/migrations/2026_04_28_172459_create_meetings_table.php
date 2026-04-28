<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('idea_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('investor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('founder_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('meeting_time');
            $table->string('meeting_link')->nullable();

            $table->enum('status',['scheduled','completed','cancelled'])->default('scheduled');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('meetings');
    }
};