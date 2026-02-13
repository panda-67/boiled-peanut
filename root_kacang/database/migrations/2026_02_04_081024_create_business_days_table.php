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
        Schema::create('business_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            $table->date('date'); // tanggal operasional, bukan created_at
            $table->enum('status', ['open', 'closed'])->default('open');

            $table->timestamp('opened_at');
            $table->foreignUuid('opened_by')->constrained('users');

            $table->timestamp('closed_at')->nullable();
            $table->foreignUuid('closed_by')->nullable()->constrained('users');

            $table->timestamps();

            // Hindari duplikasi tanggal per location
            $table->unique(['location_id', 'date']);

            // Generated column untuk menjamin hanya satu OPEN per location
            $table->unsignedBigInteger('open_location_lock')
                ->nullable()
                ->storedAs("CASE WHEN status = 'open' THEN location_id ELSE NULL END");

            $table->unique('open_location_lock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_days');
    }
};
