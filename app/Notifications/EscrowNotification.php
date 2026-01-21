<?php

namespace App\Notifications;

use App\Models\EscrowTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EscrowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EscrowTransaction $escrow,
        public string $type // 'held', 'released', 'deducted'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->escrow->amount, 2);
        $orderId = $this->escrow->order->order_number;

        return match ($this->type) {
            'held' => (new MailMessage)
                ->subject("Escrow Held for Order {$orderId}")
                ->greeting("Payment Secured")
                ->line("Funds of {$amount} {$this->escrow->currency} have been held in escrow for order {$orderId}.")
                ->line("Return deadline: {$this->escrow->order->return_deadline->format('Y-m-d')}")
                ->action('View Order', route('orders.show', $this->escrow->order))
                ->line('Return the item in good condition by the deadline for a full refund.'),

            'released' => (new MailMessage)
                ->subject("Escrow Released for Order {$orderId}")
                ->greeting("Transaction Complete")
                ->line("Escrow of {$amount} {$this->escrow->currency} has been released.")
                ->line("Reason: {$this->escrow->reason_code}")
                ->action('View Order', route('orders.show', $this->escrow->order)),

            'deducted' => (new MailMessage)
                ->subject("Damage Deduction from Order {$orderId}")
                ->greeting("Escrow Partially Deducted")
                ->line("A damage fee of {$amount} {$this->escrow->currency} has been deducted from your escrow.")
                ->line("Reason: Item damage")
                ->action('View Details', route('orders.show', $this->escrow->order))
                ->line('You can appeal this decision if you disagree.'),

            default => new MailMessage(),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'escrow_id' => $this->escrow->id,
            'order_id' => $this->escrow->order->id,
            'type' => $this->type,
            'amount' => $this->escrow->amount,
            'status' => $this->escrow->status,
        ];
    }
}
