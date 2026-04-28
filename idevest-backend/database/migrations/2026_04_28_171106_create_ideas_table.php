<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('project_name');
            $table->text('description');

            $table->string('sector');
            $table->string('location')->nullable();

            $table->decimal('capital_required', 15, 2)->default(0);
            $table->decimal('expected_revenue', 15, 2)->nullable();

            $table->integer('team_size')->default(1);
            $table->text('team_experience')->nullable();

            $table->text('competitors')->nullable();
            $table->text('competitive_advantage')->nullable();

            $table->text('target_audience')->nullable();
            $table->string('timeline')->nullable();

            $table->integer('ai_score')->default(0);
            $table->integer('risk_score')->default(0);
            $table->integer('market_score')->default(0);
            $table->integer('innovation_score')->default(0);
            $table->integer('execution_score')->default(0);
            $table->integer('investment_score')->default(0);

            $table->enum('decision', ['INVEST', 'WATCH', 'PASS'])->default('WATCH');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->longText('ai_evaluation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};