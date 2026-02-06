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
        Schema::create('product_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3);

            $table->enum('type', ['in', 'out']);
            $table->string('reference_type'); // 'sale', 'production'
            $table->unsignedBigInteger('reference_id');

            $table->text('note')->nullable();
            $table->dateTime('date');
            $table->timestamps();

            $table->index(['product_id', 'reference_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transactions');
    }
};
