<?php

namespace Database\Seeders;

use App\Models\Location;
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
        $owner = Role::firstWhere('code', 'owner');
        $manager = Role::firstWhere('code', 'manager');
        $op = Role::firstWhere('code', 'operator');
        Location::factory()->central()->create();

        User::create([
            'name' => 'Ahmad',
            'email' => 'ahmad@kacang.test',
            'password' => Hash::make('ahmad123'),
            'role_id' => $owner->id
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@kacang.test',
            'password' => Hash::make('password'),
            'role_id' => $manager->id
        ]);

        User::create([
            'name' => 'Budi',
            'email' => 'budi@kacang.test',
            'password' => Hash::make('123'),
            'role_id' => $op->id
        ]);
    }
}
