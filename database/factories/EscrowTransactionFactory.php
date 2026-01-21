<?php

namespace Database\Factories;

use App\Models\EscrowTransaction;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscrowTransactionFactory extends Factory
{
    protected $model = EscrowTransaction::class;

    public function definition(): array
    {
        $order = Order::factory()->create();
        $amount = $this->faker->numberBetween(50, 500);

        return [
            'order_id' => $order->id,
            'wallet_transaction_id' => null,
            'amount' => $amount,
            'currency' => 'MDL',
            'status' => 'held',
            'held_at' => now(),
            'reason_code' => 'order_purchase',
            'notes' => $this->faker->sentence(),
        ];
    }

    public function released(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'released',
                'released_at' => now(),
            ];
        });
    }

    public function deducted(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'deducted',
                'released_at' => now(),
                'reason_code' => 'damage_fee',
            ];
        });
    }

    public function refunded(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'refunded',
                'released_at' => now(),
                'reason_code' => 'order_cancelled',
            ];
        });
    }
}
