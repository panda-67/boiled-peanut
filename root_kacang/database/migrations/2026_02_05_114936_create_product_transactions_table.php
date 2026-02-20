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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();

            $table->dateTime('date');
            $table->enum('type', ['in', 'out', 'reserve', 'release', 'settle']);
            $table->decimal('quantity', 15, 3);

            $table->string('reference_type'); // 'sale', 'production'
            $table->uuid('reference_id');

            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'reference_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_id', 'location_id', 'type']);
            $table->index(['product_id', 'location_id']);
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
