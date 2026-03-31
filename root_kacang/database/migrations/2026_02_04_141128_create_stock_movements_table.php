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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();

            $table->dateTime('date');
            $table->enum('type', ['in', 'out', 'reserve', 'release', 'settle']);
            $table->decimal('quantity', 15, 3);

            $table->string('reference_type')->nullable(); // 'sale', 'production'
            $table->uuid('reference_id')->nullable();

            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'reference_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['item_id', 'location_id', 'type']);
            $table->index(['item_id', 'location_id']);

            /* $table->foreignId('material_id')->constrained()->cascadeOnDelete(); */
            /* $table->foreignId('location_id')->constrained()->restrictOnDelete(); */
            /**/
            /* $table->decimal('quantity', 10, 2); // + masuk, - keluar */
            /* $table->string('type'); // purchase | production | adjustment | initial */
            /* $table->text('note')->nullable(); */
            /**/
            /* $table->string('reference_type')->nullable(); */
            /* $table->uuid('reference_id')->nullable(); */
            /**/
            /* $table->timestamps(); */
            /**/
            /* $table->index(['material_id', 'reference_type']); */
            /* $table->index(['reference_type', 'reference_id']); */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
