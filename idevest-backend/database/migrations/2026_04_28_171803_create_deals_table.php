<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('idea_id')->constrained()->onDelete('cascade');

            $table->foreignId('founder_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('investor_id')->constrained('users')->onDelete('cascade');

            $table->decimal('investment_amount', 15, 2);
            $table->decimal('equity_percentage', 5, 2);
            $table->decimal('valuation', 15, 2);

            $table->decimal('platform_fee', 15, 2)->default(0);

            $table->enum('status', ['pending', 'active', 'closed', 'cancelled'])->default('pending');

            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};