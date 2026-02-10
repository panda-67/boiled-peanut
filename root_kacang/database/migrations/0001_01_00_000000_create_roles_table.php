<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // su, owner, manager, operator, spectator
            $table->string('name');
        });

        DB::table('roles')->insert([
            ['id' => uuid_create(), 'code' => 'su',        'name' => 'Superuser'],
            ['id' => uuid_create(), 'code' => 'owner',     'name' => 'Owner'],
            ['id' => uuid_create(), 'code' => 'manager',   'name' => 'Manager'],
            ['id' => uuid_create(), 'code' => 'operator',  'name' => 'Operator'],
            ['id' => uuid_create(), 'code' => 'spectator', 'name' => 'Spectator'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
