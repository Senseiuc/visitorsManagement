<?php

namespace App\Notifications;

use App\Channels\TermiiSmsChannel;
use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisitCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Visit $visit
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // SMS notifications disabled for now
        // Only send SMS if user has a phone number
        // if (!empty($notifiable->phone_number)) {
        //     $channels[] = TermiiSmsChannel::class;
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $visitor = $this->visit->visitor;
        $visitorName = $visitor->full_name ?? 'A visitor';

        return (new MailMessage)
            ->subject('New Visit Notification - ' . $visitorName)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have a new visit scheduled.')
            ->line('**Visitor:** ' . $visitorName)
            ->line('**Email:** ' . ($visitor->email ?? 'N/A'))
            ->line('**Phone:** ' . ($visitor->mobile ?? 'N/A'))
            ->line('**Reason:** ' . ($this->visit->reason->name ?? 'N/A'))
            ->line('**Time:** ' . $this->visit->created_at->format('M d, Y h:i A'));
//            ->action('View Visit Details', url('/admin/visits/' . $this->visit->id))
//            ->line('Please approve or manage this visit from your dashboard.');
    }

    /**
     * Get the Termii SMS representation of the notification.
     */
    public function toTermii(object $notifiable): array
    {
        $visitor = $this->visit->visitor;
        $visitorName = $visitor->full_name ?? 'A visitor';
        $reason = $this->visit->reason->name ?? 'visit';

        $message = "VMS Alert: {$visitorName} has checked in for {$reason}";

        return [
            'to' => $notifiable->phone_number,
            'message' => $message,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'visit_id' => $this->visit->id,
            'visitor_name' => $this->visit->visitor->full_name,
            'reason' => $this->visit->reason->name ?? null,
        ];
    }
}
