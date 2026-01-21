<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Dispute $dispute,
        public string $type // 'created', 'evidence_submitted', 'resolved', 'appealed'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->dispute->title;
        $orderId = $this->dispute->order->order_number;

        return match ($this->type) {
            'created' => (new MailMessage)
                ->subject("Dispute Created: {$title}")
                ->greeting("Dispute Opened on Order {$orderId}")
                ->line("A dispute has been created for your order.")
                ->line("Title: {$title}")
                ->line("Status: {$this->dispute->status}")
                ->action('View Dispute', route('disputes.show', $this->dispute))
                ->line('Please review and respond with evidence if needed.'),

            'evidence_submitted' => (new MailMessage)
                ->subject("New Evidence in Dispute: {$title}")
                ->greeting("Evidence Submitted to Dispute")
                ->line("New evidence has been submitted to your dispute.")
                ->action('View Dispute', route('disputes.show', $this->dispute))
                ->line('Review the new evidence and respond if necessary.'),

            'resolved' => (new MailMessage)
                ->subject("Dispute Resolved: {$title}")
                ->greeting("Dispute Resolution")
                ->line("Your dispute has been resolved.")
                ->line("Resolution: {$this->dispute->final_resolution}")
                ->line("Notes: {$this->dispute->resolution_notes}")
                ->action('View Dispute', route('disputes.show', $this->dispute))
                ->line('Thank you for using our dispute resolution system.'),

            'appealed' => (new MailMessage)
                ->subject("Dispute Appealed: {$title}")
                ->greeting("Appeal Submitted")
                ->line("A dispute has been appealed.")
                ->action('View Dispute', route('disputes.show', $this->dispute))
                ->line('The case is under re-review.'),

            default => new MailMessage(),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'order_id' => $this->dispute->order->id,
            'type' => $this->type,
            'title' => $this->dispute->title,
            'status' => $this->dispute->status,
        ];
    }
}
