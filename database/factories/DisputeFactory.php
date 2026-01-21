<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        $order = Order::factory()->create();

        return [
            'order_id' => $order->id,
            'initiator_id' => $order->user_id,
            'respondent_id' => $order->seller_id ?? User::factory()->create()->id,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => 'open',
            'reason' => $this->faker->randomElement(['item_damaged', 'not_as_described', 'not_received', 'other']),
            'damage_claim_amount' => $this->faker->numberBetween(10, 500),
        ];
    }

    public function resolved(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'resolved',
                'resolver_id' => User::factory()->admin()->create()->id,
                'resolved_at' => now(),
                'final_resolution' => $this->faker->randomElement(['initiator_wins', 'respondent_wins', 'compromise']),
                'resolution_notes' => $this->faker->paragraph(),
                'approved_deduction_amount' => $this->faker->numberBetween(0, 300),
            ];
        });
    }

    public function appealed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'awaiting_resolution',
                'is_appealed' => true,
                'appeal_notes' => $this->faker->paragraph(),
                'appeal_evidence_urls' => [$this->faker->imageUrl()],
            ];
        });
    }
}
