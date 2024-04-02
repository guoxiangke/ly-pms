<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        $email = "admin@admin.com";
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($email),
        ]);

        $this->call([
            TagsSeeder::class,
            LyMetaSeeder::class,
            LtsMetaSeeder::class,
        ]);
    }
}
