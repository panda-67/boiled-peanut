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
        Schema::create('daily_cogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_day_id')
                ->unique()
                ->nullable()
                ->constrained('business_days')
                ->restrictOnDelete();
            $table->integer('quantity_sold');
            $table->integer('average_cost');
            $table->integer('cogs_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_cogs');
    }
};
