<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('full_legal_name');
            $table->string('national_id')->unique();

            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->default('Egypt');
            $table->text('address')->nullable();

            $table->string('id_card_front')->nullable();
            $table->string('id_card_back')->nullable();

            $table->longText('ai_verification_result')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};