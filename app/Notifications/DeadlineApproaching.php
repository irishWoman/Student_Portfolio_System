<?php

namespace App\Notifications;

use App\Models\Deadline;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deadline reminder. Stored in the database for the in-app bell, and mailed
 * when MAIL_MAILER is configured; on the default `log` mailer it just writes to
 * the log, which is what you want during development.
 */
class DeadlineApproaching extends Notification
{
    use Queueable;

    public function __construct(public Deadline $deadline) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'deadline_id' => $this->deadline->id,
            'title' => $this->deadline->title,
            'section' => $this->deadline->sectionTitle(),
            'due_at' => $this->deadline->due_at->toDateTimeString(),
            'days_left' => (int) now()->startOfDay()->diffInDays($this->deadline->due_at->startOfDay(), false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = (int) now()->startOfDay()->diffInDays($this->deadline->due_at->startOfDay(), false);

        return (new MailMessage)
            ->subject('Portfolio deadline: '.$this->deadline->title)
            ->greeting('Hello '.$notifiable->name)
            ->line($this->deadline->sectionTitle().' is due on '.$this->deadline->due_at->format('F j, Y \a\t g:i a').'.')
            ->line($days === 1 ? 'That is tomorrow.' : "That is in {$days} days.")
            ->action('Open your portfolio', route('portfolio.index'))
            ->line($this->deadline->instructions ?: 'Submitting early leaves time for your evaluator to send it back if something is missing.');
    }
}
