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
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique()->nullable();
            $table->string('name', 150);

            $table->enum('type', ['RAW', 'FINISHED', 'SEMI']);

            $table->string('unit', 20);

            $table->boolean('is_stocked')->default(true);
            $table->boolean('is_sellable')->default(false);
            $table->boolean('is_purchasable')->default(true);

            $table->decimal('default_unit_cost', 14, 2)->default(0);
            $table->decimal('default_price', 14, 2)->default(0);

            $table->decimal('min_stock', 14, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
