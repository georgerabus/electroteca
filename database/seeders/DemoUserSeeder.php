<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $enabled = filter_var(env('DEMO_USER_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $email = env('DEMO_USER_EMAIL');
        $password = env('DEMO_USER_PASSWORD');

        if (!$enabled || !$email || !$password) {
            return;
        }

        $name = env('DEMO_USER_NAME', 'Demo Admin');
        $isAdmin = filter_var(env('DEMO_USER_IS_ADMIN', true), FILTER_VALIDATE_BOOLEAN);

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'admin' => $isAdmin,
            ]
        );
    }
}
