<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        $demoEnabled = filter_var(env('DEMO_USER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        if (!app()->environment('production') && $demoEnabled) {
            $this->call([
                DemoUserSeeder::class,
            ]);
        }
    }
}
