<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddWalletCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:add {user} {amount} {--reason=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add credits to a user\'s wallet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIdentifier = $this->argument('user');
        $amount = (float) $this->argument('amount');
        $reason = $this->option('reason') ?? 'Manual credit addition';

        // Try to find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (!$user) {
            $this->error("User not found: {$userIdentifier}");
            return 1;
        }

        if ($amount <= 0) {
            $this->error('Amount must be greater than 0');
            return 1;
        }

        try {
            $transaction = $user->creditWallet($amount, $reason);
            $this->info("Successfully added {$amount} credits to {$user->name}'s wallet.");
            $this->info("New balance: {$user->fresh()->wallet_balance} CR");
            $this->info("Transaction ID: {$transaction->id}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
