<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_2fa_code',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'email_2fa_expires_at' => 'datetime',
            'email_2fa_verified_at' => 'datetime',
            'wallet_balance' => 'decimal:2',
            'subscription_renews_at' => 'datetime',
            'admin' => 'boolean',
        ];
    }

    /**
     * Wallet transactions for the user.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Credit the user's wallet.
     */
    public function creditWallet(float $amount, ?string $reason = null, array $meta = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        $transaction = $this->walletTransactions()->create([
            'amount' => $amount,
            'type' => 'credit',
            'reason' => $reason,
            'meta' => $meta,
        ]);

        $this->increment('wallet_balance', $amount);

        return $transaction;
    }

    /**
     * Debit the user's wallet, preventing negative balances.
     */
    public function debitWallet(float $amount, ?string $reason = null, array $meta = []): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive.');
        }

        if ($this->wallet_balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        $transaction = $this->walletTransactions()->create([
            'amount' => $amount,
            'type' => 'debit',
            'reason' => $reason,
            'meta' => $meta,
        ]);

        $this->decrement('wallet_balance', $amount);

        return $transaction;
    }
}
