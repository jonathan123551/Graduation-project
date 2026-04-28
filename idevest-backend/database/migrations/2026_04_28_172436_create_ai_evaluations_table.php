<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();

            $table->integer('innovation_score')->default(0);
            $table->integer('market_score')->default(0);
            $table->integer('execution_score')->default(0);
            $table->integer('investment_score')->default(0);
            $table->integer('risk_score')->default(0);
            $table->integer('overall_score')->default(0);

            $table->string('decision')->nullable();
            $table->longText('analysis')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_evaluations');
    }
};