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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();

            $table->dateTime('sale_date');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // workflow + payment
            $table->enum('status', [
                'draft',     // belum dikunci
                'confirmed', // stok sudah dikurangi
                'settled',   // uang sudah diterima
                'cancelled',
            ])->default('draft');

            $table->enum('payment_status', [
                'unpaid',
                'paid',
            ])->default('unpaid');

            $table->enum('payment_method', [
                'cash',
                'transfer',
                'ewallet',
            ])->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->restrictOnDelete();
            $table->foreignId('business_day_id')->nullable()
                ->constrained('business_days')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(['business_day_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
