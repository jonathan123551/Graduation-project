<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('payment_gateway')->nullable();
            $table->string('transaction_id')->nullable();

            $table->enum('status',['pending','paid','failed'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};