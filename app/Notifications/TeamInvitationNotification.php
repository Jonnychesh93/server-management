<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public TeamInvitation $invitation) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->invitation->team;

        return (new MailMessage)
            ->subject("You've been invited to join {$team->name}")
            ->line("You have been invited to join the \"{$team->name}\" team.")
            ->action('Accept Invitation', route('team-invitations.show', $this->invitation->token))
            ->line('If you did not expect this invitation, you may ignore this email.');
    }
}
