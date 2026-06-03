<?php

namespace App\Notifications;

use App\Models\UserExport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public UserExport $export) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your export is ready')
            ->line("Your {$this->export->format} export of {$this->export->resource} is ready ({$this->export->row_count} rows).")
            ->action('View exports', url(route('exports.index')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'Success',
            'message' => "Export ready: {$this->export->resource} ({$this->export->format})",
            'link' => route('exports.index', absolute: false),
        ];
    }
}
