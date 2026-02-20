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
        Schema::create('manager_active_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->unique(); // one active context per manager

            $table->foreignId('location_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_active_locations');
    }
};
