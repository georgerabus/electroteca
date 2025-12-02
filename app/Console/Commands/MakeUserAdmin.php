<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin (by email or ID)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIdentifier = $this->argument('user');

        // Try to find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return 1;
        }

        if ($user->admin) {
            $this->warn("User {$user->name} ({$user->email}) is already an admin.");
            return 0;
        }

        $user->update(['admin' => true]);

        $this->info("✓ User {$user->name} ({$user->email}) is now an admin!");
        return 0;
    }
}
