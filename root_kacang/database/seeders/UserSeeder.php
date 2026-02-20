<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::where('code', 'manager')->first();
        $op = Role::where('code', 'operator')->first();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@kacang.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id
        ]);

        User::create([
            'name' => 'Budi',
            'email' => 'budi@kacang.test',
            'password' => Hash::make('123'),
            'role_id' => $op->id
        ]);
    }
}
