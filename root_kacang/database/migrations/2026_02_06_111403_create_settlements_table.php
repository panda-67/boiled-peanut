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
        Schema::create('settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique(); // 1 sale = 1 settlement (fase sekarang)

            $table->decimal('amount_received', 15, 2);
            $table->string('method')->default('cash')->index();
            $table->timestamp('received_at');

            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['sale_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
